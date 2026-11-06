<?php

/**
 * This file is part of web3.php package.
 *
 * (c) Oleg Abrosimov <olegabrosimovnsk@gmail.com>
 *
 * @license MIT
 */

namespace Web3\Providers;

use Ratchet\Client\Connector;

/**
 * A Pawl connector that drops the `Origin` header from the handshake.
 *
 * This lives in its own file, and not next to `WsClient`, for one reason:
 * `WsClient` must be loadable when the WebSocket transport is NOT installed.
 * A `class X extends Connector` declaration resolves its parent the moment the
 * file is included, so a class like this one inside `WsClient.php` made the
 * whole WebSocket client unloadable without `ratchet/pawl` - and Composer's PSR-4
 * loader includes the file as soon as anything mentions `WsClient`.
 *
 * Kept in the same namespace as `WsClient` so the class name is unchanged for
 * existing callers. Referenced only from `WsClient::set_ws_connector()`, after
 * `WsClient::is_available()` has confirmed that Pawl is present, so this file is
 * never loaded in an installation without the WebSocket transport.
 *
 * @package Web3
 */
class NoOriginHeaderConnector extends Connector
{
    /**
     * generateRequest
     *
     * Same as the parent, minus the `Origin` header: some nodes reject a
     * handshake that carries an origin they do not expect from a server.
     *
     * @param string $url
     * @param array $subProtocols
     * @param array $headers
     * @return \Psr\Http\Message\RequestInterface
     */
    public function generateRequest($url, array $subProtocols, array $headers)
    {
        return parent::generateRequest($url, $subProtocols, $headers)->withoutHeader('Origin');
    }
}
