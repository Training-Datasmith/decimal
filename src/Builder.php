<?php

declare (strict_types=1);
/**
 * This file is part of the PrestaShop\Decimal package
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */
namespace Presta_Shop\Decimal;

/**
 * Builds Number instances
 */
class Builder
{
    /**
     * Pattern for most numbers
     */
    public const NUMBER_PATTERN = "/^(?<sign>[-+])?(?<integerPart>\\d+)?(?:\\.(?<fractionalPart>\\d+)(?<exponentPart>[eE](?<exponentSign>[-+])(?<exponent>\\d+))?)?\$/";
    /**
     * Pattern for integer numbers in scientific notation (rare but supported by spec)
     */
    public const INT_EXPONENTIAL_PATTERN = "/^(?<sign>[-+])?(?<integerPart>\\d+)(?<exponentPart>[eE](?<exponentSign>[-+])(?<exponent>\\d+))\$/";
    /**
     * Builds a Number from a string
     *
     * @param string $number
     */
    public static function parse_number($number): \Presta_Shop\Decimal\Decimal_Number
    {
        if (!self::it_looks_like_a_number($number, $number_parts)) {
            throw new \InvalidArgumentException(sprintf('"%s" cannot be interpreted as a number', print_r($number, true)));
        }
        $integer_part = '';
        if (array_key_exists('integerPart', $number_parts)) {
            // extract the integer part and remove leading zeroes
            $integer_part = ltrim($number_parts['integerPart'], '0');
        }
        $fractional_part = '';
        if (array_key_exists('fractionalPart', $number_parts)) {
            // extract the fractional part and remove trailing zeroes
            $fractional_part = rtrim($number_parts['fractionalPart'], '0');
        }
        $fractional_digits = strlen($fractional_part);
        $coefficient = $integer_part . $fractional_part;
        // when coefficient is '0' or a sequence of '0'
        if ('' === $coefficient) {
            $coefficient = '0';
        }
        // when the number has been provided in scientific notation
        if (array_key_exists('exponentPart', $number_parts)) {
            $given_exponent = (int) ($number_parts['exponentSign'] . $number_parts['exponent']);
            // we simply add or subtract fractional digits from the given exponent (depending if it's positive or negative)
            $fractional_digits -= $given_exponent;
            if ($fractional_digits < 0) {
                // if the resulting fractional digits is negative, it means there is no fractional part anymore
                // we need to add trailing zeroes as needed
                $coefficient = str_pad($coefficient, strlen($coefficient) - $fractional_digits, '0');
                // there's no fractional part anymore
                $fractional_digits = 0;
            }
        }
        return new Decimal_Number($number_parts['sign'] . $coefficient, $fractional_digits);
    }
    /**
     * @param string $number
     * @param array $numberParts
     */
    private static function it_looks_like_a_number($number, &$number_parts): bool
    {
        return strlen((string) $number) > 0 && (preg_match(self::NUMBER_PATTERN, $number, $number_parts) || preg_match(self::INT_EXPONENTIAL_PATTERN, $number, $number_parts));
    }
}