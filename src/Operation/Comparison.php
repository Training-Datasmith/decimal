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
 * Compares two decimal numbers
 */
class Comparison
{
    /**
     * Compares two decimal numbers.
     *
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    public function compare(Decimal_Number $a, Decimal_Number $b)
    {
        if (function_exists('bccomp')) {
            return $this->compare_using_bc_math($a, $b);
        }
        return $this->compare_without_bc_math($a, $b);
    }
    /**
     * Compares two decimal numbers using BC Math
     *
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    public function compare_using_bc_math(Decimal_Number $a, Decimal_Number $b): int
    {
        return bccomp((string) $a, (string) $b, max($a->get_exponent(), $b->get_exponent()));
    }
    /**
     * Compares two decimal numbers without using BC Math
     *
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    public function compare_without_bc_math(Decimal_Number $a, Decimal_Number $b)
    {
        $sign_compare = $this->compare_signs($a->get_sign(), $b->get_sign());
        if ($sign_compare !== 0) {
            return $sign_compare;
        }
        // signs are equal, compare regardless of sign
        $result = $this->positive_compare($a, $b);
        // inverse the result if the signs are negative
        if ($a->is_negative()) {
            return -$result;
        }
        return $result;
    }
    /**
     * Compares two decimal numbers as positive regardless of sign.
     *
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    private function positive_compare(Decimal_Number $a, Decimal_Number $b)
    {
        // compare integer length
        $int_length_compare = $this->compare_numeric(strlen($a->get_integer_part()), strlen($b->get_integer_part()));
        if ($int_length_compare !== 0) {
            return $int_length_compare;
        }
        // integer parts are equal in length, compare integer part
        $int_part_compare = $this->compare_binary($a->get_integer_part(), $b->get_integer_part());
        if ($int_part_compare !== 0) {
            return $int_part_compare;
        }
        // integer parts are equal, compare fractional part
        return $this->compare_binary($a->get_fractional_part(), $b->get_fractional_part());
    }
    /**
     * Compares positive/negative signs.
     *
     * @param string $a
     * @param string $b
     *
     * @return int Returns 0 if both signs are equal, 1 if $a is positive, and -1 if $b is positive
     */
    private function compare_signs($a, $b): int
    {
        if ($a === $b) {
            return 0;
        }
        // empty string means positive sign
        if ($a === '') {
            return 1;
        }
        return -1;
    }
    /**
     * Compares two values numerically.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    private function compare_numeric(int $a, int $b): int
    {
        if ($a < $b) {
            return -1;
        }
        if ($a > $b) {
            return 1;
        }
        return 0;
    }
    /**
     * Compares two strings binarily.
     *
     * @param string $a
     * @param string $b
     *
     * @return int returns 1 if $a > $b, -1 if $a < $b, and 0 if they are equal
     */
    private function compare_binary($a, $b): int
    {
        $comparison = strcmp($a, $b);
        if ($comparison > 0) {
            return 1;
        }
        if ($comparison < 0) {
            return -1;
        }
        return 0;
    }
}