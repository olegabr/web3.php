<?php

namespace Vendor\Package\Dependencies\Web3 {
    /**
     * The same classes after php-scoper rewrote their namespace: the scoped
     * package is `Vendor\Package\Dependencies\Web3\*`, not `Web3\*`. The class
     * NAMES stay untouched - only the namespace is prefixed.
     */
    class Eth extends \Web3\Eth
    {
    }

    class Net extends \Web3\Net
    {
    }

    class Web3 extends \Web3\Web3
    {
    }
}

namespace Test\Unit {
    use Test\TestCase;
    use Vendor\Package\Dependencies\Web3\Eth as ScopedEth;
    use Vendor\Package\Dependencies\Web3\Net as ScopedNet;
    use Vendor\Package\Dependencies\Web3\Web3 as ScopedWeb3;

    /**
     * A provider that records the JSON-RPC methods instead of talking to a node.
     */
    class RecordingProvider extends \Web3\Providers\Provider
    {
        /**
         * Method names in the order they were sent.
         *
         * @var string[]
         */
        public $sent = [];

        /**
         * construct
         *
         * @param string $host
         * @param float $timeout
         */
        public function __construct($host = 'http://localhost:8545', $timeout = 1)
        {
            $this->host = $host;
            $this->timeout = $timeout;
        }

        /**
         * send
         *
         * @param \Web3\Methods\Method $method
         * @param callable|null $callback
         * @return mixed
         */
        public function send($method, $callback)
        {
            $this->sent[] = $method->getMethod();

            if (is_callable($callback)) {
                call_user_func($callback, null, null);
            }
            return null;
        }
    }

    /**
     * The RPC namespace is taken from the class name, so a scoped build (every
     * plugin that runs php-scoper) keeps working.
     *
     * Regression: `__call()` used to take the second segment of the fully
     * qualified name, which is `EthereumWallet` (or any other vendor prefix)
     * once php-scoper rewrites the namespace. The derived method then was
     * `ethereumwallet_getBalance`, the allow list rejected it and every chain
     * call in a scoped plugin died with "Unallowed rpc method".
     */
    class ScopedNamespaceTest extends TestCase
    {
        /**
         * The RPC method keeps the class-name prefix in a scoped namespace.
         *
         * @return void
         */
        public function testTheScopedEthClassStillCallsEthMethods()
        {
            $provider = new RecordingProvider();
            $eth = new ScopedEth($provider);

            $eth->getBalance('0x70997970C51812dc3A010C7d01b50e0d17dc79C8', function ($err, $balance) {
            });

            $this->assertSame(['eth_getBalance'], $provider->sent);
        }

        /**
         * The same for the other modules.
         *
         * @return void
         */
        public function testTheScopedModulesKeepTheirOwnPrefix()
        {
            $provider = new RecordingProvider();

            $net = new ScopedNet($provider);
            $net->version(function ($err, $version) {
            });

            $this->assertSame(['net_version'], $provider->sent);

            $scopedWeb3 = new ScopedWeb3($provider);
            $scopedWeb3->clientVersion(function ($err, $version) {
            });

            $this->assertSame(['net_version', 'web3_clientVersion'], $provider->sent);
        }

        /**
         * An unscoped class is unaffected by the change.
         *
         * @return void
         */
        public function testTheUnscopedClassIsUnchanged()
        {
            $provider = new RecordingProvider();
            $eth = new \Web3\Eth($provider);

            $eth->blockNumber(function ($err, $number) {
            });

            $this->assertSame(['eth_blockNumber'], $provider->sent);
        }
    }
}
