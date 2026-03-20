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
use Presta_Shop\Decimal\Exception\Division_By_Zero_Exception;
/**
 * Computes the division between two decimal numbers.
 */
class Division
{
    public const DEFAULT_PRECISION = 6;
    /**
     * Performs the division.
     *
     * A target maximum precision is required in order to handle potential infinite number of decimals
     * (e.g. 1/3 = 0.3333333...).
     *
     * If the division yields more decimal positions than the requested precision,
     * the remaining decimals are truncated, with **no rounding**.
     *
     * @param DecimalNumber $a Dividend
     * @param DecimalNumber $b Divisor
     * @param int $precision Maximum decimal precision
     *
     * @return DecimalNumber Result of the division
     *
     * @throws DivisionByZeroException
     */
    public function compute(Decimal_Number $a, Decimal_Number $b, $precision = self::DEFAULT_PRECISION)
    {
        if (function_exists('bcdiv')) {
            return $this->compute_using_bc_math($a, $b, $precision);
        }
        return $this->compute_without_bc_math($a, $b, $precision);
    }
    /**
     * Performs the division using BC Math
     *
     * @param DecimalNumber $a Dividend
     * @param DecimalNumber $b Divisor
     * @param int $precision Maximum decimal precision
     *
     * @return DecimalNumber Result of the division
     *
     * @throws DivisionByZeroException
     */
    public function compute_using_bc_math(Decimal_Number $a, Decimal_Number $b, $precision = self::DEFAULT_PRECISION): \Presta_Shop\Decimal\Decimal_Number
    {
        if ((string) $b === '0') {
            throw new Division_By_Zero_Exception();
        }
        return new Decimal_Number(bcdiv($a, $b, $precision));
    }
    /**
     * Performs the division without BC Math
     *
     * @param DecimalNumber $a Dividend
     * @param DecimalNumber $b Divisor
     * @param int $precision Maximum decimal precision
     *
     * @return DecimalNumber Result of the division
     *
     * @throws DivisionByZeroException
     */
    public function compute_without_bc_math(Decimal_Number $a, Decimal_Number $b, $precision = self::DEFAULT_PRECISION)
    {
        $b_string = (string) $b;
        if ('0' === $b_string) {
            throw new Division_By_Zero_Exception();
        }
        $a_string = (string) $a;
        // 0 as dividend always yields 0
        if ('0' === $a_string) {
            return $a;
        }
        // 1 as divisor always yields the dividend
        if ('1' === $b_string) {
            return $a;
        }
        // -1 as divisor always yields the the inverted dividend
        if ('-1' === $b_string) {
            return $a->invert();
        }
        // if dividend and divisor are equal, the result is always 1
        if ($a->equals($b)) {
            return new Decimal_Number('1');
        }
        $a_precision = $a->get_precision();
        $b_precision = $b->get_precision();
        $max_precision = max($a_precision, $b_precision);
        if ($max_precision > 0) {
            // make $a and $b integers by multiplying both by 10^(maximum number of decimals)
            $a = $a->to_magnitude($max_precision);
            $b = $b->to_magnitude($max_precision);
        }
        return $this->integer_division($a, $b, $precision);
    }
    /**
     * Computes the division between two integer DecimalNumbers
     *
     * @param DecimalNumber $a Dividend
     * @param DecimalNumber $b Divisor
     * @param int $precision Maximum number of decimals to try
     */
    private function integer_division(Decimal_Number $a, Decimal_Number $b, $precision): \Presta_Shop\Decimal\Decimal_Number
    {
        $dividend = $a->get_coefficient();
        $divisor = new Decimal_Number($b->get_coefficient());
        $dividend_length = strlen($dividend);
        $result = '';
        $exponent = 0;
        $current_sequence = '';
        for ($i = 0; $i < $dividend_length; ++$i) {
            // append digits until we get a number big enough to divide
            $current_sequence .= $dividend[$i];
            if ($current_sequence < $divisor) {
                if (!empty($result)) {
                    $result .= '0';
                }
            } else {
                // subtract divisor as many times as we can
                $remainder = new Decimal_Number($current_sequence);
                $multiple = 0;
                do {
                    ++$multiple;
                    $remainder = $remainder->minus($divisor);
                } while ($remainder->is_greater_or_equal_than($divisor));
                $result .= (string) $multiple;
                // reset sequence to the reminder
                $current_sequence = (string) $remainder;
            }
            // add up to $precision decimals
            if ($current_sequence > 0 && $i === $dividend_length - 1 && $precision > 0) {
                // "borrow" up to $precision digits
                --$precision;
                $dividend .= '0';
                ++$dividend_length;
                ++$exponent;
            }
        }
        $sign = ($a->is_negative() xor $b->is_negative()) ? '-' : '';
        return new Decimal_Number($sign . $result, $exponent);
    }
}