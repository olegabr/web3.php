<?php

/**
 * This file is part of web3.php package.
 * Reference: ccxt
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

 namespace Web3\Providers;

use Ratchet\Client\Connector;
use React;
use React\Async;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\Timer;
use React\Promise\Timer\TimeoutException;

use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;

use Exception;
use RuntimeException;

/**
 * WebSocket client for the `ws://` / `wss://` transport.
 *
 * **The WebSocket transport is optional.** `ratchet/pawl` and the ReactPHP
 * packages it needs are a `suggest`, not a `require`, of this library: a plugin
 * that only talks JSON-RPC over HTTP must not carry a WebSocket stack (and must
 * not ask its bundler to prefix one). Install them explicitly to use `ws://`:
 *
 *     composer require ratchet/pawl
 *
 * The `use` statements above are inert - PHP resolves them lazily - so this
 * class is safe to autoload in an installation without the transport. What was
 * NOT safe was the `NoOriginHeaderConnector` subclass that used to live in this
 * file: `class X extends Connector` resolves its parent at include time, and
 * Composer includes this file as soon as anything mentions `WsClient`. That
 * class now lives in `NoOriginHeaderConnector.php`, loaded only from
 * `set_ws_connector()` after `is_available()` said yes.
 *
 * `is_available()` answers whether the transport can be used, and the entry
 * points throw a `RuntimeException` naming the missing package instead of dying
 * with "Class not found" inside ReactPHP.
 *
 * @package Web3
 */
class WsClient {

    public $url;

    public $error;
    public $connectionEstablished;
    public $timeout = 1;
    // is server with feature ping/pong and keep alive?
    // can remove this if not?
    public $pingInterval;
    public $keepAlive = 30;
    public $maxPingPongMisses = 2.0;
    public $lastPong = null;
    public $ping = null;
    public $connection = null;
    public $connected;
    public $isConnected = false;
    public $noOriginHeader = true;
    public $headers = [];

    // ratchet/pawl/reactphp stuff
    public $connector = null;

    protected $deferredConnected;
    protected $deferredMessages = [];

    /**
     * Whether the optional WebSocket transport is installed.
     *
     * Answers the question a caller should ask before it builds a `WsProvider`,
     * so a plugin can degrade to HTTP instead of catching an exception.
     *
     * @return bool
     */
    public static function is_available()
    {
        // `React\Promise\Timer` is a namespace with functions, not a class, so
        // the timeout helper has to be asked for with `function_exists()`; a
        // `class_exists('React\Promise\Timer')` is always false and would report
        // the transport as missing while it is installed.
        return class_exists('Ratchet\\Client\\Connector')
            && class_exists('React\\EventLoop\\Loop')
            && class_exists('React\\Promise\\Deferred')
            && function_exists('React\\Async\\coroutine')
            && function_exists('React\\Promise\\Timer\\timeout');
    }

    /**
     * Refuse to run when the optional transport is missing.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function require_transport()
    {
        if (!self::is_available()) {
            throw new RuntimeException(
                'The WebSocket transport is not installed: this library declares '
                . 'ratchet/pawl and react/* as an optional dependency. Run '
                . '"composer require ratchet/pawl" to use ws:// or wss:// endpoints, '
                . 'or connect over http(s) instead.'
            );
        }
    }

    public function resolve($result) {
        $deferred = array_shift($this->deferredMessages);
        if ($deferred) {
            $deferred->resolve($result);
        }
    }

    public function reject($result) {
        foreach ($this->deferredMessages as $deferred) {
            $deferred->reject($result);
        }
    }

    public function __construct(
            $url,
            $timeout = 1,
            $keepAlive = 30,
            $maxPingPongMisses = 2.0,
            $noOriginHeader = true,
            $headers = []
        ) {

        $this->url = $url;

        $this->timeout = $timeout;
        $this->keepAlive = $keepAlive;
        $this->maxPingPongMisses = $maxPingPongMisses;
        $this->noOriginHeader = $noOriginHeader;
        $this->headers = $headers;

        $deferred = new Deferred();
        $this->connected = $deferred->promise();
        $this->deferredConnected = $deferred;
    }

    public function set_ws_connector() {
        $this->require_transport();
        $react_default_connector = new React\Socket\Connector();
        if ($this->noOriginHeader) {
            $this->connector = new NoOriginHeaderConnector(Loop::get(), $react_default_connector);
        } else {
            $this->connector = new Connector(Loop::get(), $react_default_connector);
        }
    }

    public function create_connection() {
        $connect = function () {
            $timeout = $this->timeout;
            $headers = $this->headers;
            $promise = call_user_func($this->connector, $this->url, [], $headers);
            Timer\timeout($promise, $timeout, Loop::get())->then(
                function($connection) {
                    $this->connection = $connection;
                    $this->connection->on('message', array($this, 'on_message'));
                    $this->connection->on('close', array($this, 'on_close'));
                    $this->connection->on('error', array($this, 'on_error'));
                    $this->connection->on('pong', array($this, 'on_pong'));
                    $this->isConnected = true;
                    $this->connectionEstablished = $this->milliseconds();
                    $this->deferredConnected->resolve($this->url);
                    $this->set_ping_interval();
                },
                function(\Exception $error) {
                    // the ordering of these exceptions is important
                    // since one inherits another
                    if ($error instanceof TimeoutException) {
                    } else if ($error instanceof RuntimeException) {
                        // connection failed or rejected
                    }
                    $this->on_error($error);
                }
            );
        };
        if (function_exists('React\\Async\\async')) {
            $connect = Async\async($connect);
        }
        return Async\coroutine($connect);
    }

    public function connect($backoff_delay = 0) {
        if (!$this->connection) {
            $this->connection = true;
            if ($backoff_delay) {
                $callback = array($this, 'create_connection');
                Loop::addTimer(((float)$backoff_delay), $callback);
            } else {
                $this->create_connection();
            }
        }
        return $this->connected;
    }

    public function send($data) {
        $send = function () use ($data) {
            $this->connection->send($data);
            $deferred = new Deferred();
            $this->deferredMessages[] = $deferred;
            return Async\await($deferred->promise());
        };
        if (function_exists('React\\Async\\async')) {
            $send = Async\async($send);
        }
        return Async\coroutine($send);
    }

    public function close() {
        $this->connection->close();
    }

    public function on_pong($message) {
        $this->lastPong = $this->milliseconds();
    }

    public function on_error($error) {
        $this->error = $error;
        $this->reset($error);
    }

    public function on_close($message) {
        if (!$this->error) {
            // todo: exception types for server-side disconnects
            $this->reset(new RuntimeException($message));
        }
    }

    public function on_message(Message $message) {

        try {
            $message = (string) $message;
        } catch (Exception $e) {
            // reset with a json encoding error?
        }

        try {
            $this->resolve($message);
        } catch (Exception $error) {
            $this->reject($error);
        }
    }

    public function reset($error) {
        $this->clear_ping_interval();
        $this->reject($error);
    }

    public function set_ping_interval() {
        if ($this->keepAlive) {
            $delay = $this->keepAlive;
            $this->pingInterval = Loop::addPeriodicTimer($delay, array($this, 'on_ping_interval'));
        }
    }

    public function clear_ping_interval() {
        if ($this->pingInterval) {
            Loop::cancelTimer($this->pingInterval);
        }
    }

    public function milliseconds() {
        list($msec, $sec) = explode(' ', microtime());
        return (int)($sec . substr($msec, 2, 3));
    }

    public function on_ping_interval() {
        if ($this->keepAlive && $this->isConnected) {
            $now = $this->milliseconds();
            $this->lastPong = isset ($this->lastPong) ? $this->lastPong : $now;
            if (($this->lastPong + $this->keepAlive * $this->maxPingPongMisses) < $now) {
                $this->on_error(new RuntimeException('Connection to ' . $this->url . ' timed out due to a ping-pong keepalive missing on time'));
            } else {
                $this->connection->send(new Frame('', true, Frame::OP_PING));
            }
        }
    }
};
