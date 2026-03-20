<?php

declare (strict_types=1);
/**
 * This file is part of the PrestaShop\Decimal package
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */
namespace Presta_Shop\Decimal\Operation;

use Presta_Shop\Decimal\Decimal_Number;
/**
 * Computes relative magnitude changes on a decimal number
 */
class Magnitude_Change
{
    /**
     * Multiplies a number by 10^$exponent.
     *
     * Examples:
     * ```php
     * $n = new Decimal\Number('123.45678');
     * $o = new Decimal\Operation\MagnitudeChange();
     * $o->compute($n, 2);  // 12345.678
     * $o->compute($n, 6);  // 123456780
     * $o->compute($n, -2); // 1.2345678
     * $o->compute($n, -6); // 0.00012345678
     * ```
     *
     * @param int $exponent
     * @return DecimalNumber
     */
    public function compute(Decimal_Number $number, $exponent)
    {
        $exponent = (int) $exponent;
        if ($exponent === 0) {
            return $number;
        }
        $resulting_exponent = $exponent - $number->get_exponent();
        if ($resulting_exponent <= 0) {
            return new Decimal_Number($number->get_sign() . $number->get_coefficient(), abs($resulting_exponent));
        }
        // add zeroes
        $target_length = strlen($number->get_coefficient()) + $resulting_exponent;
        return new Decimal_Number($number->get_sign() . str_pad($number->get_coefficient(), $target_length, '0'));
    }
}