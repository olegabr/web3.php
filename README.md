# web3.php

[![CI](https://github.com/olegabr/web3.php/actions/workflows/ci.yml/badge.svg)](https://github.com/olegabr/web3.php/actions/workflows/ci.yml)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/olegabr/web3.php/blob/master/LICENSE)


A php interface for interacting with the Ethereum blockchain and ecosystem.

This repository is the EthereumICO fork ([olegabr/web3.php](https://github.com/olegabr/web3.php))
of [web3p/web3.php](https://github.com/web3p/web3.php). It keeps the `Web3` namespace and
adds PHP 8.x support, typed transactions, EIP-712 and the ABI fixes.

# Install

The fork is not published on Packagist, so point Composer at the repository:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/olegabr/web3.php" }
    ],
    "require": {
        "olegabr/web3.php": "dev-master"
    }
}
```

Pin a tagged release instead of `dev-master` when one covers your needs.


# Usage

### New instance
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
```

### Using provider
```php
use Web3\Web3;
use Web3\Providers\HttpProvider;

$web3 = new Web3(new HttpProvider('http://localhost:8545'));

// timeout
$web3 = new Web3(new HttpProvider('http://localhost:8545', 0.1));
```

### You can use callback to each rpc call:
```php
$web3->clientVersion(function ($err, $version) {
    if ($err !== null) {
        // do something
        return;
    }
    if (isset($version)) {
        echo 'Client version: ' . $version;
    }
});
```

### Eth
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
$eth = $web3->eth;
```

Or

```php
use Web3\Eth;

$eth = new Eth('http://localhost:8545');
```

### Net
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
$net = $web3->net;
```

Or

```php
use Web3\Net;

$net = new Net('http://localhost:8545');
```

### Batch

web3
```php
$web3->batch(true);
$web3->clientVersion();
$web3->hash('0x1234');
$web3->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        // it may throw exception or array of exception depends on error type
        // connection error: throw exception
        // json rpc error: array of exception
        return;
    }
    // do something
});
```

eth

```php
$eth->batch(true);
$eth->protocolVersion();
$eth->syncing();

$eth->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

net
```php
$net->batch(true);
$net->version();
$net->listening();

$net->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

personal
```php
$personal->batch(true);
$personal->listAccounts();
$personal->newAccount('123456');

$personal->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

### Contract

```php
use Web3\Contract;

$contract = new Contract('http://localhost:8545', $abi);

// deploy contract
$contract->bytecode($bytecode)->new($params, $callback);

// call contract function
$contract->at($contractAddress)->call($functionName, $params, $callback);

// change function state
$contract->at($contractAddress)->send($functionName, $params, $callback);

// estimate deploy contract gas
$contract->bytecode($bytecode)->estimateGas($params, $callback);

// estimate function gas
$contract->at($contractAddress)->estimateGas($functionName, $params, $callback);

// get constructor data
$constructorData = $contract->bytecode($bytecode)->getData($params);

// get function data
$functionData = $contract->at($contractAddress)->getData($functionName, $params);
```

# Assign value to outside scope(from callback scope to outside scope)
Due to callback is not like javascript callback, 
if we need to assign value to outside scope, 
we need to assign reference to callback.
```php
$newAccount = '';

$web3->personal->newAccount('123456', function ($err, $account) use (&$newAccount) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    $newAccount = $account;
    echo 'New account: ' . $account . PHP_EOL;
});
```

# Examples

To run examples, you need to run ethereum blockchain local (testrpc).

If you are using docker as development machain, you can try [ethdock](https://github.com/sc0vu/ethdock) to run local ethereum blockchain, just simply run `docker-compose up -d testrpc` and expose the `8545` port.

# Develop

### Local php cli installed

1. Clone the repo and install packages.
```
git clone https://github.com/sc0Vu/web3.php.git && cd web3.php && composer install
```

2. Run test script.
```
vendor/bin/phpunit
```

### Docker container

1. Clone the repo and run docker container.
```
git clone https://github.com/sc0Vu/web3.php.git
```

2. Copy web3.php to web3.php/docker/app directory and start container.
```
cp files docker/app && docker-compose up -d php ganache
```

3. Enter php container and install packages.
```
docker-compose exec php ash
```

4. Change testHost in `TestCase.php`
```
/**
 * testHost
 * 
 * @var string
 */
protected $testHost = 'http://ganache:8545';
```

5. Run test script
```
vendor/bin/phpunit
```

###### Install packages
Enter container first
```
docker-compose exec php ash
```

1. gmp
```
apk add gmp-dev
docker-php-ext-install gmp
```

2. bcmath
```
docker-php-ext-install bcmath
```

###### Remove extension
Move the extension config from `/usr/local/etc/php/conf.d/`
```
mv /usr/local/etc/php/conf.d/extension-config-name to/directory
```

# API

Todo.

# License
MIT
