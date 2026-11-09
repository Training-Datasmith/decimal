<?php

declare(strict_types=1);
/**
 * This file is part of the Presta_Shop\Decimal package
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Presta_Shop\Decimal\Test\Operation;

use PHPUnit\Framework\TestCase;
use Presta_Shop\Decimal\Decimal_Number;
use Presta_Shop\Decimal\Operation\Magnitude_Change;

class Magnitude_ChangeTest extends TestCase
{
    /**
     * Given a decimal number
     * When changing its magnitude to a specific exponent
     * Then we should get the result of multiplying it by 10^exponent
     * (Same as shifting the decimal dot to the left or to the right by "exponent" characters)
     *
     * @param string $number
     * @param int $exponent
     * @param string $expected
     *
     * @dataProvider provide_test_cases
     */
    public function test_it_changes_magnitude($number, $exponent, $expected)
    {
        $n = new Decimal_Number($number);

        $result = (new Magnitude_Change())->compute($n, $exponent);

        $this->assertSame($expected, (string) $result);
    }

    public function provide_test_cases()
    {
        return [
            ['123.45678', 0, '123.45678'],
            ['123.45678', 1, '1234.5678'],
            ['123.45678', 2, '12345.678'],
            ['123.45678', 3, '123456.78'],
            ['123.45678', 6, '123456780'],
            ['123.45678', 8, '12345678000'],
            ['123.45678', -1, '12.345678'],
            ['123.45678', -2, '1.2345678'],
            ['123.45678', -3, '0.12345678'],
            ['123.45678', -6, '0.00012345678'],
            ['123.45678', -8, '0.0000012345678'],
        ];
    }
}
