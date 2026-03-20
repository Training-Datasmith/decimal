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
 * Retrocompatible name for DecimalNumber
 *
 * @deprecated use DecimalNumber instead
 */
class Number extends Decimal_Number
{
    /**
     * {@inheritdoc}
     */
    public function __construct($number, $exponent = null)
    {
        @trigger_error(__FUNCTION__ . 'is deprecated since version 1.4. Use DecimalNumber instead.', E_USER_DEPRECATED);
        parent::__construct($number, $exponent);
    }
}