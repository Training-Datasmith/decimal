<?php

declare (strict_types=1);
/**
 * This file is part of the PrestaShop\Decimal package
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */
namespace Presta_Shop\Decimal;

use InvalidArgumentException;
use Presta_Shop\Decimal\Operation\Rounding;
/**
 * Decimal number.
 *
 * Allows for arbitrary precision math operations.
 */
class Decimal_Number
{
    /**
     * Indicates if the number is negative
     *
     * @var bool
     */
    private $is_negative = false;
    /**
     * Integer representation of this number
     *
     * @var string
     */
    private $coefficient = '';
    /**
     * Scientific notation exponent. For practical reasons, it's always stored as a positive value.
     *
     * @var int
     */
    private $exponent = 0;
    /**
     * Number constructor.
     *
     * This constructor can be used in two ways:
     *
     * 1) With a number string:
     *
     * ```php
     * (string) new Number('0.123456'); // -> '0.123456'
     * ```
     *
     * 2) With an integer string as coefficient and an exponent
     *
     * ```php
     * // 123456 * 10^(-6)
     * (string) new Number('123456', 6); // -> '0.123456'
     * ```
     *
     * Note: decimal positions must always be a positive number.
     *
     * @param string $number Number or coefficient
     * @param int|null $exponent [default=null] If provided, the number can be considered as the negative
     *                           exponent of the scientific notation, or the number of fractional digits
     */
    public function __construct($number, $exponent = null)
    {
        if (!is_string($number)) {
            throw new InvalidArgumentException(sprintf('Invalid type - expected string, but got (%s) "%s"', gettype($number), print_r($number, true)));
        }
        if (null === $exponent) {
            $decimal_number = Builder::parse_number($number);
            $number = $decimal_number->get_sign() . $decimal_number->get_coefficient();
            $exponent = $decimal_number->get_exponent();
        }
        $this->init_from_scientific_notation($number, $exponent);
        if ('0' === $this->coefficient) {
            // make sure the sign is always positive for zero
            $this->is_negative = false;
        }
    }
    /**
     * Returns the integer part of the number.
     * Note that this does NOT include the sign.
     *
     * @return string
     */
    public function get_integer_part()
    {
        if ('0' === $this->coefficient) {
            return $this->coefficient;
        }
        if (0 === $this->exponent) {
            return $this->coefficient;
        }
        if ($this->exponent >= strlen($this->coefficient)) {
            return '0';
        }
        return substr($this->coefficient, 0, -$this->exponent);
    }
    /**
     * Returns the fractional part of the number.
     * Note that this does NOT include the sign.
     */
    public function get_fractional_part(): string
    {
        if (0 === $this->exponent || '0' === $this->coefficient) {
            return '0';
        }
        if ($this->exponent > strlen($this->coefficient)) {
            return str_pad($this->coefficient, $this->exponent, '0', STR_PAD_LEFT);
        }
        return substr($this->coefficient, -$this->exponent);
    }
    /**
     * Returns the number of digits in the fractional part.
     *
     * @see self::getExponent() This method is an alias of getExponent().
     *
     * @return int
     */
    public function get_precision()
    {
        return $this->get_exponent();
    }
    /**
     * Returns the number's sign.
     * Note that this method will return an empty string if the number is positive!
     *
     * @return string '-' if negative, empty string if positive
     */
    public function get_sign(): string
    {
        return $this->is_negative ? '-' : '';
    }
    /**
     * Returns the exponent of this number. For practical reasons, this exponent is always >= 0.
     *
     * This value can also be interpreted as the number of significant digits on the fractional part.
     *
     * @return int
     */
    public function get_exponent()
    {
        return $this->exponent;
    }
    /**
     * Returns the raw number as stored internally. This coefficient is always an integer.
     *
     * It can be transformed to float by computing:
     * ```
     * getCoefficient() * 10^(-getExponent())
     * ```
     *
     * @return string
     */
    public function get_coefficient()
    {
        return $this->coefficient;
    }
    /**
     * Returns a string representation of this object
     */
    public function __toString(): string
    {
        $output = $this->get_sign() . $this->get_integer_part();
        $fractional_part = $this->get_fractional_part();
        if ('0' !== $fractional_part) {
            $output .= '.' . $fractional_part;
        }
        return $output;
    }
    /**
     * Returns the number as a string, with exactly $precision decimals
     *
     * Example:
     * ```
     * $n = new Number('123.4560');
     * (string) $n->round(1); // '123.4'
     * (string) $n->round(2); // '123.45'
     * (string) $n->round(3); // '123.456'
     * (string) $n->round(4); // '123.4560' (trailing zeroes are added)
     * (string) $n->round(5); // '123.45600' (trailing zeroes are added)
     * ```
     *
     * @param int $precision Exact number of desired decimals
     * @param string $roundingMode [default=Rounding::ROUND_TRUNCATE] Rounding algorithm
     */
    public function to_precision($precision, $rounding_mode = Rounding::ROUND_TRUNCATE): string
    {
        $current_precision = $this->get_precision();
        if ($precision === $current_precision) {
            return (string) $this;
        }
        $return = $this;
        if ($precision < $current_precision) {
            $return = (new Operation\Rounding())->compute($this, $precision, $rounding_mode);
        }
        if ($precision > $return->get_precision()) {
            return $return->get_sign() . $return->get_integer_part() . '.' . str_pad($return->get_fractional_part(), $precision, '0');
        }
        return (string) $return;
    }
    /**
     * Returns the number as a string, with up to $maxDecimals significant digits.
     *
     * Example:
     * ```
     * $n = new Number('123.4560');
     * (string) $n->round(1); // '123.4'
     * (string) $n->round(2); // '123.45'
     * (string) $n->round(3); // '123.456'
     * (string) $n->round(4); // '123.456' (does not add trailing zeroes)
     * (string) $n->round(5); // '123.456' (does not add trailing zeroes)
     * ```
     *
     * @param int $maxDecimals Maximum number of decimals
     * @param string $roundingMode [default=Rounding::ROUND_TRUNCATE] Rounding algorithm
     */
    public function round($max_decimals, $rounding_mode = Rounding::ROUND_TRUNCATE): string
    {
        $current_precision = $this->get_precision();
        if ($max_decimals < $current_precision) {
            return (string) (new Operation\Rounding())->compute($this, $max_decimals, $rounding_mode);
        }
        return (string) $this;
    }
    /**
     * Returns this number as a positive number
     *
     * @return self
     */
    public function to_positive()
    {
        if (!$this->is_negative) {
            return $this;
        }
        return $this->invert();
    }
    /**
     * Returns this number as a negative number
     *
     * @return self
     */
    public function to_negative()
    {
        if ($this->is_negative) {
            return $this;
        }
        return $this->invert();
    }
    /**
     * Returns the computed result of adding another number to this one
     *
     * @param self $addend Number to add
     *
     * @return self
     */
    public function plus(self $addend)
    {
        return (new Operation\Addition())->compute($this, $addend);
    }
    /**
     * Returns the computed result of subtracting another number to this one
     *
     * @param self $subtrahend Number to subtract
     *
     * @return self
     */
    public function minus(self $subtrahend)
    {
        return (new Operation\Subtraction())->compute($this, $subtrahend);
    }
    /**
     * Returns the computed result of multiplying this number with another one
     *
     *
     * @return self
     */
    public function times(self $factor)
    {
        return (new Operation\Multiplication())->compute($this, $factor);
    }
    /**
     * Returns the computed result of dividing this number by another one, with up to $precision number of decimals.
     *
     * A target maximum precision is required in order to handle potential infinite number of decimals
     * (e.g. 1/3 = 0.3333333...).
     *
     * If the division yields more decimal positions than the requested precision,
     * the remaining decimals are truncated, with **no rounding**.
     *
     * @param int $precision [optional] By default, up to Operation\Division::DEFAULT_PRECISION number of decimals
     *
     * @return self
     * @throws Exception\DivisionByZeroException
     */
    public function divided_by(self $divisor, $precision = Operation\Division::DEFAULT_PRECISION)
    {
        return (new Operation\Division())->compute($this, $divisor, $precision);
    }
    /**
     * Indicates if this number equals zero
     */
    public function equals_zero(): bool
    {
        return '0' == $this->get_coefficient();
    }
    /**
     * Indicates if this number is greater than the provided one
     *
     *
     */
    public function is_greater_than(self $number): bool
    {
        return 1 === (new Operation\Comparison())->compare($this, $number);
    }
    /**
     * Indicates if this number is greater than zero
     */
    public function is_greater_than_zero(): bool
    {
        return $this->is_positive() && !$this->equals_zero();
    }
    /**
     * Indicates if this number is greater or equal than zero
     *
     * @return bool
     */
    public function is_greater_or_equal_than_zero()
    {
        return $this->is_positive();
    }
    /**
     * Indicates if this number is greater or equal compared to the provided one
     *
     *
     */
    public function is_greater_or_equal_than(self $number): bool
    {
        return 0 <= (new Operation\Comparison())->compare($this, $number);
    }
    /**
     * Indicates if this number is lower than zero
     */
    public function is_lower_than_zero(): bool
    {
        return $this->is_negative() && !$this->equals_zero();
    }
    /**
     * Indicates if this number is lower or equal than zero
     */
    public function is_lower_or_equal_than_zero(): bool
    {
        if ($this->is_negative()) {
            return true;
        }
        return $this->equals_zero();
    }
    /**
     * Indicates if this number is greater than the provided one
     *
     *
     */
    public function is_lower_than(self $number): bool
    {
        return -1 === (new Operation\Comparison())->compare($this, $number);
    }
    /**
     * Indicates if this number is lower or equal compared to the provided one
     *
     *
     */
    public function is_lower_or_equal_than(self $number): bool
    {
        return 0 >= (new Operation\Comparison())->compare($this, $number);
    }
    /**
     * Indicates if this number is positive
     */
    public function is_positive(): bool
    {
        return !$this->is_negative;
    }
    /**
     * Indicates if this number is negative
     *
     * @return bool
     */
    public function is_negative()
    {
        return $this->is_negative;
    }
    /**
     * Indicates if this number equals another one
     *
     *
     */
    public function equals(self $number): bool
    {
        return $this->is_negative === $number->is_negative && $this->coefficient === $number->get_coefficient() && $this->exponent === $number->get_exponent();
    }
    /**
     * Returns the additive inverse of this number (that is, N * -1).
     *
     * @return static
     */
    public function invert(): self
    {
        // invert sign
        $sign = $this->is_negative ? '' : '-';
        return new static($sign . $this->get_coefficient(), $this->get_exponent());
    }
    /**
     * Creates a new copy of this number multiplied by 10^$exponent
     *
     * @param int $exponent
     *
     * @return static
     */
    public function to_magnitude($exponent)
    {
        return (new Operation\Magnitude_Change())->compute($this, $exponent);
    }
    /**
     * Initializes the number using a coefficient and exponent
     *
     * @param int $exponent
     */
    private function init_from_scientific_notation(string $coefficient, $exponent): void
    {
        if ($exponent < 0) {
            throw new InvalidArgumentException(sprintf('Invalid value for exponent. Expected a positive integer or 0, but got "%s"', $coefficient));
        }
        if (!preg_match("/^(?<sign>[-+])?(?<integerPart>\\d+)\$/", $coefficient, $parts)) {
            throw new InvalidArgumentException(sprintf('"%s" cannot be interpreted as a number', $coefficient));
        }
        $this->is_negative = '-' === $parts['sign'];
        $this->exponent = (int) $exponent;
        // trim leading zeroes
        $this->coefficient = ltrim($parts['integerPart'], '0');
        // when coefficient is '0' or a sequence of '0'
        if ('' === $this->coefficient) {
            $this->exponent = 0;
            $this->coefficient = '0';
            return;
        }
        $this->remove_trailing_zeroes_if_needed();
    }
    /**
     * Removes trailing zeroes from the fractional part and adjusts the exponent accordingly
     */
    private function remove_trailing_zeroes_if_needed(): void
    {
        $exponent = $this->get_exponent();
        $coefficient = $this->get_coefficient();
        // trim trailing zeroes from the fractional part
        // for example 1000e-1 => 100.0
        if (0 < $exponent && '0' === substr($coefficient, -1)) {
            $fractional_part = $this->get_fractional_part();
            $trailing_zeroes_to_remove = 0;
            for ($i = $exponent - 1; $i >= 0; --$i) {
                if ('0' !== $fractional_part[$i]) {
                    break;
                }
                ++$trailing_zeroes_to_remove;
            }
            if ($trailing_zeroes_to_remove > 0) {
                $this->coefficient = substr($coefficient, 0, -$trailing_zeroes_to_remove);
                $this->exponent = $exponent - $trailing_zeroes_to_remove;
            }
        }
    }
}