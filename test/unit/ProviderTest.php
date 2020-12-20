<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\RequestManagers\RequestManager;
use Web3\Providers\Provider;

class ProviderTest extends TestCase
{
    /**
     * testSetRequestManager
     * 
     * @return void
     */
    public function testSetRequestManager()
    {
        $requestManager = new RequestManager($this->testHost);
        $provider = new Provider($requestManager);

        $this->assertEquals($provider->requestManager->host, $this->testHost);

        $requestManager = new RequestManager($this->testRinkebyHost);
        $provider->requestManager = $requestManager;

        $this->assertEquals($provider->requestManager->host, $this->testHost);
    }
}