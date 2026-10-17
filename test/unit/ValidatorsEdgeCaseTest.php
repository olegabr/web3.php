<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Validators\CallValidator;
use Web3\Validators\FilterValidator;
use Web3\Validators\PostValidator;
use Web3\Validators\ShhFilterValidator;
use Web3\Validators\TransactionValidator;

class ValidatorsEdgeCaseTest extends TestCase
{
    /**
     * testTransactionValidatorValid
     *
     * @return void
     */
    public function testTransactionValidatorValid()
    {
        $this->assertTrue(TransactionValidator::validate([
            'from' => '0xb60e8dd61c5d32be8058bb8eb970870f07233155',
            'to' => '0xd46e8dd67c5d32be8058bb8eb970870f07244567',
            'gas' => '0x76c0',
            'gasPrice' => '0x9184e72a000',
            'value' => '0x9184e72a',
            'data' => '0xd46e8dd6',
            'nonce' => '0x1',
        ]));
        // an empty "to" is allowed, it means contract creation
        $this->assertTrue(TransactionValidator::validate([
            'from' => '0xb60e8dd61c5d32be8058bb8eb970870f07233155',
            'to' => '',
        ]));
    }

    /**
     * testTransactionValidatorInvalidFields
     *
     * @return void
     */
    public function testTransactionValidatorInvalidFields()
    {
        $from = '0xb60e8dd61c5d32be8058bb8eb970870f07233155';

        $this->assertFalse(TransactionValidator::validate(['to' => $from]));
        $this->assertFalse(TransactionValidator::validate(['from' => '0x1234']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'to' => '0x1234']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'gas' => 'not hex']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'gasPrice' => 'not hex']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'value' => 'not hex']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'nonce' => 'not hex']));
        $this->assertFalse(TransactionValidator::validate(['from' => $from, 'data' => 'not hex']));
        $this->assertFalse(TransactionValidator::validate('not an array'));
    }

    /**
     * testPostValidatorInvalidFields
     *
     * @return void
     */
    public function testPostValidatorInvalidFields()
    {
        $valid = [
            'from' => '0xb60e8dd61c5d32be8058bb8eb970870f07233155',
            'to' => '0xd46e8dd67c5d32be8058bb8eb970870f07244567',
            'topics' => ['0x0000000000000000000000000000000000000000000000000000000000000001'],
            'payload' => '0x7b2274797065223a2274657374227d',
            'priority' => '0x64',
            'ttl' => '0x64',
        ];
        $this->assertTrue(PostValidator::validate($valid));

        $this->assertFalse(PostValidator::validate('not an array'));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['to' => 'nope'])));
        $this->assertFalse(PostValidator::validate(array_diff_key($valid, ['topics' => 1])));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['topics' => 'nope'])));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['topics' => ['nope']])));
        $this->assertFalse(PostValidator::validate(array_diff_key($valid, ['payload' => 1])));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['payload' => 'nope'])));
        $this->assertFalse(PostValidator::validate(array_diff_key($valid, ['priority' => 1])));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['priority' => 'nope'])));
        $this->assertFalse(PostValidator::validate(array_diff_key($valid, ['ttl' => 1])));
        $this->assertFalse(PostValidator::validate(array_merge($valid, ['ttl' => 'nope'])));
    }

    /**
     * testFilterValidatorInvalidFields
     *
     * @return void
     */
    public function testFilterValidatorInvalidFields()
    {
        $this->assertFalse(FilterValidator::validate('not an array'));
        $this->assertFalse(FilterValidator::validate(['fromBlock' => 'nope']));
        $this->assertFalse(FilterValidator::validate(['toBlock' => 'nope']));
        $this->assertFalse(FilterValidator::validate(['address' => '0x1234']));
        $this->assertFalse(FilterValidator::validate(['address' => ['0x1234']]));
        $this->assertFalse(FilterValidator::validate(['topics' => ['0xzz']]));
        $this->assertFalse(FilterValidator::validate(['topics' => [['0xzz']]]));

        $this->assertTrue(FilterValidator::validate([
            'fromBlock' => '0x1',
            'toBlock' => 'latest',
            'address' => '0xb60e8dd61c5d32be8058bb8eb970870f07233155',
            'topics' => [null, '0x0000000000000000000000000000000000000000000000000000000000000001'],
        ]));
    }

    /**
     * testShhFilterValidator
     *
     * @return void
     */
    public function testShhFilterValidator()
    {
        $this->assertFalse(ShhFilterValidator::validate('not an array'));
        $this->assertFalse(ShhFilterValidator::validate(['topics' => ['0xzz']]));
        $this->assertFalse(ShhFilterValidator::validate(['topics' => [['0xzz']]]));
        $this->assertTrue(ShhFilterValidator::validate(['topics' => [null]]));
        $this->assertTrue(ShhFilterValidator::validate([
            'topics' => [['0x0000000000000000000000000000000000000000000000000000000000000001']],
        ]));
    }

    /**
     * testCallValidatorInvalidFields
     *
     * @return void
     */
    public function testCallValidatorInvalidFields()
    {
        $this->assertFalse(CallValidator::validate('not an array'));
        $this->assertFalse(CallValidator::validate([
            'to' => '0x1234',
            'data' => '0x1234',
        ]));
    }
}
