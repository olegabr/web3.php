<?php

/**
 * This file is part of web3.php package.
 *
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 *
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace Web3\Validators;

use Web3\Validators\IValidator;

class FloatValidator
{
    /**
     * validate
     *
     * @param float $value
     * @return bool
     */
    public static function validate($value)
    {
        // maybe change is_int and is_float and preg_match future
        return is_float($value);
    }
}
