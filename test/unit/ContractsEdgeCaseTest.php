<?php

namespace Test\Unit;

use InvalidArgumentException;
use Test\TestCase;
use Web3\Contracts\Ethabi;
use Web3\Contracts\SolidityType;
use Web3\Contracts\Types\Boolean;
use Web3\Contracts\Types\Bytes;
use Web3\Contracts\Types\DynamicBytes;
use Web3\Formatters\IntegerFormatter;

/**
 * SolidityType with a pair of accessors to reach the magic __get/__set dispatch.
 */
class SolidityTypeWithAccessors extends SolidityType
{
    private $bar = 'bar';

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($value)
    {
        $this->bar = $value;
    }

    public function isType($name)
    {
        return true;
    }

    public function inputFormat($value, $name)
    {
        return (string) $value;
    }

    public function outputFormat($value, $name)
    {
        return $value;
    }
}

/**
 * Ethabi with a pair of accessors to reach the magic __get/__set dispatch.
 */
class EthabiWithAccessors extends Ethabi
{
    private $bar = 'bar';

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($value)
    {
        $this->bar = $value;
    }
}

class ContractsEdgeCaseTest extends TestCase
{
    /**
     * testBooleanInputFormat
     *
     * @return void
     */
    public function testBooleanInputFormat()
    {
        $type = new Boolean;

        $this->assertEquals(str_repeat('0', 63) . '1', $type->inputFormat(true, 'flag'));
        $this->assertEquals(str_repeat('0', 64), $type->inputFormat(false, 'flag'));
        $this->assertTrue($type->outputFormat(str_repeat('0', 63) . '1', 'flag'));
        $this->assertFalse($type->outputFormat(str_repeat('0', 64), 'flag'));
    }

    /**
     * testBooleanInputFormatInvalid
     *
     * @return void
     */
    public function testBooleanInputFormatInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value to inputFormat function must be boolean.');
        (new Boolean)->inputFormat('true', 'flag');
    }

    /**
     * testBytesInputFormat
     *
     * @return void
     */
    public function testBytesInputFormat()
    {
        $type = new Bytes;

        $this->assertEquals('1234' . str_repeat('0', 60), $type->inputFormat('0x1234', 'data'));
        // odd length hex is left padded to a whole byte
        $this->assertEquals('0123' . str_repeat('0', 60), $type->inputFormat('0x123', 'data'));
        $this->assertEquals('0x1234' . str_repeat('0', 60), $type->outputFormat('1234' . str_repeat('0', 60), 'data'));
    }

    /**
     * testBytesInputFormatInvalid
     *
     * @return void
     */
    public function testBytesInputFormatInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value to inputFormat must be hex bytes.');
        (new Bytes)->inputFormat('not hex', 'data');
    }

    /**
     * testBytesInputFormatTooLong
     *
     * @return void
     */
    public function testBytesInputFormatTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value to inputFormat is too long.');
        (new Bytes)->inputFormat('0x' . str_repeat('ab', 33), 'data');
    }

    /**
     * testDynamicBytes
     *
     * @return void
     */
    public function testDynamicBytes()
    {
        $type = new DynamicBytes;

        $this->assertTrue($type->isType('bytes'));
        $this->assertTrue($type->isType('bytes[]'));
        $this->assertFalse($type->isType('byte'));
        $this->assertTrue($type->isDynamicType());

        $encoded = $type->inputFormat('0x1234', 'data');
        // 32 byte length prefix holding 2, then the right padded payload
        $this->assertEquals(str_repeat('0', 63) . '2' . '1234' . str_repeat('0', 60), $encoded);
        $this->assertEquals('0x1234', $type->outputFormat($encoded, 'data'));

        // odd length hex is padded, not rejected
        $this->assertEquals(
            str_repeat('0', 63) . '2' . '0123' . str_repeat('0', 60),
            $type->inputFormat('0x123', 'data')
        );
    }

    /**
     * testDynamicBytesInputFormatInvalid
     *
     * @return void
     */
    public function testDynamicBytesInputFormatInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value to inputFormat must be hex bytes.');
        (new DynamicBytes)->inputFormat('not hex', 'data');
    }

    /**
     * testIntegerFormatter
     *
     * @return void
     */
    public function testIntegerFormatter()
    {
        $this->assertEquals(str_repeat('0', 63) . '1', IntegerFormatter::format(1));
        $this->assertEquals(str_repeat('0', 63) . '1', IntegerFormatter::format('0x1'));
        $this->assertEquals('1', IntegerFormatter::format('0x0000000000000000000000000000000000000000000000000000000000000001', 1));
        $this->assertEquals(str_repeat('0', 64), IntegerFormatter::format('0x0'));
    }

    /**
     * testSolidityTypeMagicAccessors
     *
     * @return void
     */
    public function testSolidityTypeMagicAccessors()
    {
        $type = new SolidityTypeWithAccessors;

        $this->assertEquals('bar', $type->bar);
        $type->bar = 'baz';
        $this->assertEquals('baz', $type->bar);

        $this->assertFalse($type->unknown);
        $type->unknown = 'value';
        $this->assertFalse(isset($type->unknown));

        $this->assertFalse((new SolidityType)->isDynamicType());
    }

    /**
     * testEthabiMagicAccessors
     *
     * @return void
     */
    public function testEthabiMagicAccessors()
    {
        $ethabi = new EthabiWithAccessors;

        $this->assertEquals('bar', $ethabi->bar);
        $ethabi->bar = 'baz';
        $this->assertEquals('baz', $ethabi->bar);

        $this->assertFalse($ethabi->unknown);
        $ethabi->unknown = 'value';

        // __callStatic is a deliberate no-op placeholder
        $this->assertNull(Ethabi::undefinedStaticCall());
    }

    /**
     * JSON ABI of a function and of an event.
     *
     * @return array
     */
    protected function abiFixtures()
    {
        $function = [
            'name' => 'balanceOf',
            'inputs' => [
                ['name' => 'owner', 'type' => 'address'],
            ],
            'outputs' => [
                ['name' => 'balance', 'type' => 'uint256'],
            ],
        ];
        $event = [
            'name' => 'Transfer',
            'inputs' => [
                ['name' => 'from', 'type' => 'address'],
                ['name' => 'to', 'type' => 'address'],
                ['name' => 'value', 'type' => 'uint256'],
            ],
        ];
        return [$function, $event];
    }

    /**
     * testEncodeSignaturesFromArrayAbi
     *
     * @return void
     */
    public function testEncodeSignaturesFromArrayAbi()
    {
        list($function, $event) = $this->abiFixtures();
        $ethabi = new Ethabi;

        $this->assertEquals('0x70a08231', $ethabi->encodeFunctionSignature($function));
        $this->assertEquals(
            (new \Web3\Utils)::sha3('Transfer(address,address,uint256)'),
            $ethabi->encodeEventSignature($event)
        );
        // a plain string is used as is
        $this->assertEquals('0x70a08231', $ethabi->encodeFunctionSignature('balanceOf(address)'));
    }

    /**
     * testEncodeSignaturesFromStdClassAbi
     *
     * @return void
     */
    public function testEncodeSignaturesFromStdClassAbi()
    {
        list($function, $event) = $this->abiFixtures();
        $ethabi = new Ethabi;

        $this->assertEquals('0x70a08231', $ethabi->encodeFunctionSignature(json_decode(json_encode($function))));
        $this->assertEquals(
            (new \Web3\Utils)::sha3('Transfer(address,address,uint256)'),
            $ethabi->encodeEventSignature(json_decode(json_encode($event)))
        );
    }

    /**
     * testEncodeParameterWithTypeArrayAbi
     *
     * @return void
     */
    public function testEncodeParameterWithTypeArrayAbi()
    {
        list($function) = $this->abiFixtures();
        $ethabi = new Ethabi;

        $expected = '0x' . str_repeat('0', 24) . 'b60e8dd61c5d32be8058bb8eb970870f07233155';

        $this->assertEquals($expected, $ethabi->encodeParameter('address', '0xb60e8dd61c5d32be8058bb8eb970870f07233155'));
        // the whole JSON ABI of the function can be passed instead of the type list
        $this->assertEquals($expected, $ethabi->encodeParameters($function, ['0xb60e8dd61c5d32be8058bb8eb970870f07233155']));
        $this->assertEquals($expected, $ethabi->encodeParameters(json_decode(json_encode($function)), ['0xb60e8dd61c5d32be8058bb8eb970870f07233155']));
    }

    /**
     * testDecodeParametersWithOutputsAbi
     *
     * @return void
     */
    public function testDecodeParametersWithOutputsAbi()
    {
        list($function) = $this->abiFixtures();
        $ethabi = new Ethabi;
        $encoded = str_repeat('0', 62) . '2a';

        $this->assertEquals('42', $ethabi->decodeParameter('uint256', $encoded)->toString());
        // passing the whole JSON ABI returns the decoded values keyed by output name
        $this->assertEquals(
            ['balance' => '42'],
            array_map(function ($bn) {
                return $bn->toString();
            }, $ethabi->decodeParameters($function, $encoded))
        );
        $this->assertEquals(
            ['balance' => '42'],
            array_map(function ($bn) {
                return $bn->toString();
            }, $ethabi->decodeParameters(json_decode(json_encode($function)), $encoded))
        );
    }

    /**
     * testEncodeParameterInvalidType
     *
     * @return void
     */
    public function testEncodeParameterInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type to encodeParameter must be string.');
        (new Ethabi)->encodeParameter(1, 'value');
    }

    /**
     * testEncodeParametersCountMismatch
     *
     * @return void
     */
    public function testEncodeParametersCountMismatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('encodeParameters number of types must equal to number of params.');
        (new Ethabi)->encodeParameters(['uint256', 'bool'], [1]);
    }

    /**
     * testEncodeParametersUnsupportedType
     *
     * @return void
     */
    public function testEncodeParametersUnsupportedType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupport solidity parameter type: nonsense');
        (new Ethabi)->encodeParameters(['nonsense'], ['value']);
    }

    /**
     * testDecodeParameterInvalidType
     *
     * @return void
     */
    public function testDecodeParameterInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type to decodeParameter must be string.');
        (new Ethabi)->decodeParameter(1, '0x0');
    }

    /**
     * testDecodeParametersInvalidParameter
     *
     * @return void
     */
    public function testDecodeParametersInvalidParameter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The type or param to decodeParameters must be string.');
        (new Ethabi)->decodeParameters(['uint256'], 1);
    }
}
