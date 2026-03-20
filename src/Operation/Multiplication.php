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
 * Computes the multiplication between two decimal numbers
 */
class Multiplication
{
    /**
     * Performs the multiplication
     *
     * @param DecimalNumber $a Left operand
     * @param DecimalNumber $b Right operand
     *
     * @return DecimalNumber Result of the multiplication
     */
    public function compute(Decimal_Number $a, Decimal_Number $b)
    {
        if (function_exists('bcmul')) {
            return $this->compute_using_bc_math($a, $b);
        }
        return $this->compute_without_bc_math($a, $b);
    }
    /**
     * Performs the multiplication using BC Math
     *
     * @param DecimalNumber $a Left operand
     * @param DecimalNumber $b Right operand
     *
     * @return DecimalNumber Result of the multiplication
     */
    public function compute_using_bc_math(Decimal_Number $a, Decimal_Number $b): \Presta_Shop\Decimal\Decimal_Number
    {
        $precision1 = $a->get_precision();
        $precision2 = $b->get_precision();
        return new Decimal_Number(bcmul($a, $b, $precision1 + $precision2));
    }
    /**
     * Performs the multiplication without BC Math
     *
     * @param DecimalNumber $a Left operand
     * @param DecimalNumber $b Right operand
     *
     * @return DecimalNumber Result of the multiplication
     */
    public function compute_without_bc_math(Decimal_Number $a, Decimal_Number $b)
    {
        $a_as_string = (string) $a;
        $b_as_string = (string) $b;
        // optimization: if either one is zero, the result is zero
        if ('0' === $a_as_string || '0' === $b_as_string) {
            return new Decimal_Number('0');
        }
        // optimization: if either one is one, the result is the other one
        if ('1' === $a_as_string) {
            return $b;
        }
        if ('1' === $b_as_string) {
            return $a;
        }
        $result = $this->multiply_strings(ltrim($a->get_coefficient(), '0'), ltrim($b->get_coefficient(), '0'));
        $sign = ($a->is_negative() xor $b->is_negative()) ? '-' : '';
        // a multiplication has at most as many decimal figures as the sum
        // of the number of decimal figures the factors have
        $exponent = $a->get_exponent() + $b->get_exponent();
        return new Decimal_Number($sign . $result, $exponent);
    }
    /**
     * Multiplies two integer numbers as strings.
     *
     * This method implements a naive "long multiplication" algorithm.
     *
     * @param string $topNumber
     * @param string $bottomNumber
     *
     * @return string
     */
    private function multiply_strings($top_number, $bottom_number)
    {
        $top_number_length = strlen($top_number);
        $bottom_number_length = strlen($bottom_number);
        if ($top_number_length < $bottom_number_length) {
            // multiplication is commutative, and this algorithm
            // performs better if the bottom number is shorter.
            return $this->multiply_strings($bottom_number, $top_number);
        }
        $step_number = 0;
        $result = new Decimal_Number('0');
        for ($i = $bottom_number_length - 1; $i >= 0; --$i) {
            $carry_over = 0;
            $partial_result = '';
            // optimization: we don't need to bother multiplying by zero
            if ($bottom_number[$i] === '0') {
                ++$step_number;
                continue;
            }
            if ($bottom_number[$i] === '1') {
                // multiplying by one is the same as copying the top number
                $partial_result = strrev($top_number);
            } else {
                // digit-by-digit multiplication using carry-over
                for ($j = $top_number_length - 1; $j >= 0; --$j) {
                    $multiplication_result = $bottom_number[$i] * $top_number[$j] + $carry_over;
                    $carry_over = floor($multiplication_result / 10);
                    $partial_result .= $multiplication_result % 10;
                }
                if ($carry_over > 0) {
                    $partial_result .= $carry_over;
                }
            }
            // pad the partial result with as many zeros as performed steps
            $padding = str_pad('', $step_number, '0');
            $partial_result = $padding . $partial_result;
            // add to the result
            $result = $result->plus(new Decimal_Number(strrev($partial_result)));
            ++$step_number;
        }
        return (string) $result;
    }
}