#!/usr/bin/env bash

ganache_port=8545
ganache-cli -g 0 -l 0 > /dev/null & 
ganachecli_pid=$!
echo "Start ganache-cli pid: $ganachecli_pid and wait for it to start"

echo "Waiting ganache to launch on ${ganache_port}..."

# see https://stackoverflow.com/a/48646792/4256005
while ! timeout 1 bash -c "echo > /dev/tcp/localhost/${ganache_port}"; do   
  sleep 1
done

sleep 3

echo "ganache launched"

vendor/bin/phpunit --coverage-clover=coverage.xml
ret=$?

kill -9 $ganachecli_pid
echo "Kill ganache-cli: ${ganachecli_pid}"

exit $ret
