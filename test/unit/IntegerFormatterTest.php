<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Formatters\IntegerFormatter;

class IntegerFormatterTest extends TestCase
{
    /**
     * formatter
     *
     * @var \Web3\Formatters\IntegerFormatter
     */
    protected $formatter;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->formatter = new IntegerFormatter;
    }

    /**
     * testFormat
     *
     * @return void
     */
    public function testFormat()
    {
        $formatter = $this->formatter;

        $hex = $formatter->format('1');
        $this->assertEquals($hex, implode('', array_fill(0, 63, '0')) . '1');

        $hex = $formatter->format('-1');
        $this->assertEquals($hex, implode('', array_fill(0, 64, 'f')));

        $hex = $formatter->format('ae');
        $this->assertEquals($hex, implode('', array_fill(0, 62, '0')) . 'ae');

        $hex = $formatter->format('1', 20);
        $this->assertEquals($hex, implode('', array_fill(0, 19, '0')) . '1');

        $hex = $formatter->format(48);
        $this->assertEquals($hex, implode('', array_fill(0, 62, '0')) . '30');

        $hex = $formatter->format('48');
        $this->assertEquals($hex, implode('', array_fill(0, 62, '0')) . '30');

        $hex = $formatter->format('115702957429935429406669899694558170938514411838120655819455549351241303268131');
        $this->assertEquals($hex, 'ffcd8d9d84b6c3a91096240d2d3e5ef0811febb1a3221680f7c236e806691b23');
        $this->assertEquals(strlen($hex), 64);

        $hex = $formatter->format('ffcd8d9d84b6c3a91096240d2d3e5ef0811febb1a3221680f7c236e806691b23');
        $this->assertEquals($hex, 'ffcd8d9d84b6c3a91096240d2d3e5ef0811febb1a3221680f7c236e806691b23');

        $hex = $formatter->format('0xffcd8d9d84b6c3a91096240d2d3e5ef0811febb1a3221680f7c236e806691b23');
        $this->assertEquals($hex, 'ffcd8d9d84b6c3a91096240d2d3e5ef0811febb1a3221680f7c236e806691b23');

        $hex = $formatter->format('fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffe');
        $this->assertEquals($hex, 'fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffe');
    }
}
