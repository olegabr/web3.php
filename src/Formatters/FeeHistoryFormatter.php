<?php

/**
 * This file is part of web3.php package.
 *
 * EIP-1559
 *
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 *
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace Web3\Formatters;

use InvalidArgumentException;
use Web3\Utils;
use Web3\Formatters\IFormatter;
use Web3\Formatters\QuantityFormatter;
use Web3\Formatters\IntegerFormatter;
use Web3\Formatters\FloatArrayFormatter;

class FeeHistoryFormatter implements IFormatter
{
    /**
     * format
     *
     * @param mixed $value
     * @return string
     */
    public static function format($value)
    {
        if (isset($value['oldestBlock'])) {
            $value['oldestBlock'] = QuantityFormatter::format($value['oldestBlock']);
        }
        if (isset($value['baseFeePerGas'])) {
            $value['baseFeePerGas'] = IntegerFormatter::format($value['baseFeePerGas']);
        }
        if (isset($value['gasUsedRatio'])) {
            $value['gasUsedRatio'] = FloatArrayFormatter::format($value['gasUsedRatio']);
        }

        if (isset($value['reward'])) {
            $value['reward'] = array_map(function($v) {
              return array_map(function($v2) {
                return IntegerFormatter::format($v2);
              }, $v);
            }, $value['reward']);
        }

        return $value;
    }
}
