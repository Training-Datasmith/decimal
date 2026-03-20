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
 * Computes the addition of two decimal numbers
 */
class Addition
{
    /**
     * Maximum safe string size in order to be confident
     * that it won't overflow the max int size when operating with it
     *
     * @var int
     */
    private $max_safe_int_string_size;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->max_safe_int_string_size = strlen((string) PHP_INT_MAX) - 1;
    }
    /**
     * Performs the addition
     *
     * @param DecimalNumber $a Base number
     * @param DecimalNumber $b Addend
     *
     * @return DecimalNumber Result of the addition
     */
    public function compute(Decimal_Number $a, Decimal_Number $b)
    {
        if (function_exists('bcadd')) {
            return $this->compute_using_bc_math($a, $b);
        }
        return $this->compute_without_bc_math($a, $b);
    }
    /**
     * Performs the addition using BC Math
     *
     * @param DecimalNumber $a Base number
     * @param DecimalNumber $b Addend
     *
     * @return DecimalNumber Result of the addition
     */
    public function compute_using_bc_math(Decimal_Number $a, Decimal_Number $b): \Presta_Shop\Decimal\Decimal_Number
    {
        $precision1 = $a->get_precision();
        $precision2 = $b->get_precision();
        return new Decimal_Number(bcadd($a, $b, max($precision1, $precision2)));
    }
    /**
     * Performs the addition without BC Math
     *
     * @param DecimalNumber $a Base number
     * @param DecimalNumber $b Addend
     *
     * @return DecimalNumber Result of the addition
     */
    public function compute_without_bc_math(Decimal_Number $a, Decimal_Number $b)
    {
        if ($a->is_negative()) {
            if ($b->is_negative()) {
                // if both numbers are negative,
                // we can just add them as positive numbers and then invert the sign
                // f(x, y) = -(|x| + |y|)
                // eg. f(-1, -2) = -(|-1| + |-2|) = -3
                // eg. f(-2, -1) = -(|-2| + |-1|) = -3
                return $this->compute_without_bc_math($a->to_positive(), $b->to_positive())->invert();
            }
            // if the number is negative and the addend positive,
            // perform an inverse subtraction by inverting the terms
            // f(x, y) = y - |x|
            // eg. f(-2, 1) = 1 - |-2| = -1
            // eg. f(-1, 2) = 2 - |-1| = 1
            // eg. f(-1, 1) = 1 - |-1| = 0
            return $b->minus($a->to_positive());
        }
        if ($b->is_negative()) {
            // if the number is positive and the addend is negative
            // perform subtraction instead: 2 - 1
            // f(x, y) = x - |y|
            // f(2, -1) = 2 - |-1| = 1
            // f(1, -2) = 1 - |-2| = -1
            // f(1, -1) = 1 - |-1| = 0
            return $a->minus($b->to_positive());
        }
        // optimization: 0 + x = x
        if ('0' === (string) $a) {
            return $b;
        }
        // optimization: x + 0 = x
        if ('0' === (string) $b) {
            return $a;
        }
        // pad coefficients with leading/trailing zeroes
        [$coeff1, $coeff2] = $this->normalize_coefficients($a, $b);
        // compute the coefficient sum
        $sum = $this->add_strings($coeff1, $coeff2);
        // both signs are equal, so we can use either
        $sign = $a->get_sign();
        // keep the bigger exponent
        $exponent = max($a->get_exponent(), $b->get_exponent());
        return new Decimal_Number($sign . $sum, $exponent);
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
     * Adds two integer numbers as strings.
     *
     * @param string $number1
     * @param string $number2
     * @param bool $fractional [default=false]
     *                         If true, the numbers will be treated as the fractional part of a number (padded with trailing zeroes).
     *                         Otherwise, they will be treated as the integer part (padded with leading zeroes).
     */
    private function add_strings($number1, $number2, $fractional = false): string
    {
        // optimization - numbers can be treated as integers as long as they don't overflow the max int size
        if ('0' !== $number1[0] && '0' !== $number2[0] && strlen($number1) <= $this->max_safe_int_string_size && strlen($number2) <= $this->max_safe_int_string_size) {
            return (string) ((int) $number1 + (int) $number2);
        }
        // find out which of the strings is longest
        $max_length = max(strlen($number1), strlen($number2));
        // add leading or trailing zeroes as needed
        $number1 = str_pad($number1, $max_length, '0', $fractional ? STR_PAD_RIGHT : STR_PAD_LEFT);
        $number2 = str_pad($number2, $max_length, '0', $fractional ? STR_PAD_RIGHT : STR_PAD_LEFT);
        $result = '';
        $carry_over = 0;
        for ($i = $max_length - 1; 0 <= $i; --$i) {
            $sum = $number1[$i] + $number2[$i] + $carry_over;
            $result .= $sum % 10;
            $carry_over = (int) ($sum >= 10);
        }
        if ($carry_over > 0) {
            $result .= '1';
        }
        return strrev($result);
    }
}