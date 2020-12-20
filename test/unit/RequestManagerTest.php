<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\RequestManagers\RequestManager;

class RequestManagerTest extends TestCase
{
    /**
     * testSetHost
     * 
     * @return void
     */
    public function testSetHost()
    {
        $requestManager = new RequestManager($this->testHost, 0.1);
        $this->assertEquals($requestManager->host, $this->testHost);
        $this->assertEquals($requestManager->timeout, 0.1);

        $requestManager->host = $this->testRinkebyHost;
        $requestManager->timeout = 1;
        $this->assertEquals($requestManager->host, $this->testHost);
        $this->assertEquals($requestManager->timeout, 0.1);
    }
}