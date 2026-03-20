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
 * Computes the subtraction of two decimal numbers
 */
class Subtraction
{
    /**
     * Performs the subtraction
     *
     * @param DecimalNumber $a Minuend
     * @param DecimalNumber $b Subtrahend
     *
     * @return DecimalNumber Result of the subtraction
     */
    public function compute(Decimal_Number $a, Decimal_Number $b)
    {
        if (function_exists('bcsub')) {
            return $this->compute_using_bc_math($a, $b);
        }
        return $this->compute_without_bc_math($a, $b);
    }
    /**
     * Performs the subtraction using BC Math
     *
     * @param DecimalNumber $a Minuend
     * @param DecimalNumber $b Subtrahend
     *
     * @return DecimalNumber Result of the subtraction
     */
    public function compute_using_bc_math(Decimal_Number $a, Decimal_Number $b): \Presta_Shop\Decimal\Decimal_Number
    {
        $precision1 = $a->get_precision();
        $precision2 = $b->get_precision();
        return new Decimal_Number(bcsub($a, $b, max($precision1, $precision2)));
    }
    /**
     * Performs the subtraction without using BC Math
     *
     * @param DecimalNumber $a Minuend
     * @param DecimalNumber $b Subtrahend
     *
     * @return DecimalNumber Result of the subtraction
     */
    public function compute_without_bc_math(Decimal_Number $a, Decimal_Number $b)
    {
        if ($a->is_negative()) {
            if ($b->is_negative()) {
                // if both minuend and subtrahend are negative
                // perform the subtraction with inverted coefficients position and sign
                // f(x, y) = |y| - |x|
                // eg. f(-1, -2) = |-2| - |-1| = 2 - 1 = 1
                // e.g. f(-2, -1) =  |-1| - |-2| = 1 - 2 = -1
                return $this->compute_without_bc_math($b->to_positive(), $a->to_positive());
            }
            // if the minuend is negative and the subtrahend is positive,
            // we can just add them as positive numbers and then invert the sign
            // f(x, y) = -(|x| + y)
            // eg. f(1, 2) = -(|-1| + 2) = -3
            // eg. f(-2, 1) = -(|-2| + 1) = -3
            return $a->to_positive()->plus($b)->to_negative();
        }
        if ($b->is_negative()) {
            // if the minuend is positive subtrahend is negative, perform an addition
            // f(x, y) = x + |y|
            // eg. f(2, -1) = 2 + |-1| = 2 + 1 = 3
            return $a->plus($b->to_positive());
        }
        // optimization: 0 - x = -x
        if ('0' === (string) $a) {
            return !$b->is_negative() ? $b->to_negative() : $b;
        }
        // optimization: x - 0 = x
        if ('0' === (string) $b) {
            return $a;
        }
        // pad coefficients with leading/trailing zeroes
        [$coeff1, $coeff2] = $this->normalize_coefficients($a, $b);
        // compute the coefficient subtraction
        if ($a->is_greater_than($b)) {
            $sub = $this->subtract_strings($coeff1, $coeff2);
            $sign = '';
        } else {
            $sub = $this->subtract_strings($coeff2, $coeff1);
            $sign = '-';
        }
        // keep the bigger exponent
        $exponent = max($a->get_exponent(), $b->get_exponent());
        return new Decimal_Number($sign . $sub, $exponent);
    }
    /**
     * Normalizes coefficients by adding leading or trailing zeroes as needed so that both are the same length
     *
     *
     * @return array An array containing the normalized coefficients
     */
    private function normalize_coefficients(Decimal_Number $a, Decimal_Number $b): array
    {
        $exp1 = $a->get_exponent();
        $exp2 = $b->get_exponent();
        $coeff1 = $a->get_coefficient();
        $coeff2 = $b->get_coefficient();
        // add trailing zeroes if needed
        if ($exp1 > $exp2) {
            $coeff2 = str_pad($coeff2, strlen($coeff2) + $exp1 - $exp2, '0', STR_PAD_RIGHT);
        } elseif ($exp1 < $exp2) {
            $coeff1 = str_pad($coeff1, strlen($coeff1) + $exp2 - $exp1, '0', STR_PAD_RIGHT);
        }
        $len1 = strlen($coeff1);
        $len2 = strlen($coeff2);
        // add leading zeroes if needed
        if ($len1 > $len2) {
            $coeff2 = str_pad($coeff2, $len1, '0', STR_PAD_LEFT);
        } elseif ($len1 < $len2) {
            $coeff1 = str_pad($coeff1, $len2, '0', STR_PAD_LEFT);
        }
        return [$coeff1, $coeff2];
    }
    /**
     * Subtracts $number2 to $number1.
     * For this algorithm to work, $number1 has to be >= $number 2.
     *
     * @param string $number1
     * @param string $number2
     * @param bool $fractional [default=false]
     *                         If true, the numbers will be treated as the fractional part of a number (padded with trailing zeroes).
     *                         Otherwise, they will be treated as the integer part (padded with leading zeroes).
     */
    private function subtract_strings($number1, $number2, $fractional = false): string
    {
        // find out which of the strings is longest
        $max_length = max(strlen($number1), strlen($number2));
        // add leading or trailing zeroes as needed
        $number1 = str_pad($number1, $max_length, '0', $fractional ? STR_PAD_RIGHT : STR_PAD_LEFT);
        $number2 = str_pad($number2, $max_length, '0', $fractional ? STR_PAD_RIGHT : STR_PAD_LEFT);
        $result = '';
        $carry_over = 0;
        for ($i = $max_length - 1; 0 <= $i; --$i) {
            $operand1 = $number1[$i] - $carry_over;
            $operand2 = $number2[$i];
            if ($operand1 >= $operand2) {
                $result .= $operand1 - $operand2;
                $carry_over = 0;
            } else {
                $result .= 10 + $operand1 - $operand2;
                $carry_over = 1;
            }
        }
        return strrev($result);
    }
}