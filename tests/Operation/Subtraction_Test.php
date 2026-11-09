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
use Presta_Shop\Decimal\Operation\Subtraction;

class Subtraction_Test extends TestCase
{
    /**
     * Given two decimal numbers
     * When computing the subtraction operation
     * Then we should get the result of subtracting those numbers
     *
     * @param string $number1
     * @param string $number2
     * @param string $expected_result
     *
     * @dataProvider provide_numbers_to_subtract
     */
    public function test_it_subtracts_numbers($number1, $number2, $expected_result)
    {
        $n1 = new Decimal_Number($number1);
        $n2 = new Decimal_Number($number2);

        $operation = new Subtraction();
        $result1 = $operation->compute_using_bc_math($n1, $n2);
        $result2 = $operation->compute_without_bc_math($n1, $n2);

        $this->assertSame($expected_result, (string) $result1, "Failed asserting $number1 - $number2 = $expected_result (BC Math)");
        $this->assertSame($expected_result, (string) $result2, "Failed asserting $number1 - $number2 = $expected_result");
    }

    public function provide_numbers_to_subtract()
    {
        return [
            ['0', '0', '0'],
            ['0', '5', '-5'],
            ['0', '5.1', '-5.1'],
            ['2', '1', '1'],
            ['1', '2', '-1'],
            ['-1', '2', '-3'],
            ['1', '-2', '3'],
            ['-1', '-2', '1'],
            ['5', '0', '5'],
            ['5.1', '0', '5.1'],
            ['1.234', '5', '-3.766'],
            ['5', '1.234', '3.766'],
            ['10', '0.0000000', '10'],
            ['0.0000000', '10', '-10'],
            ['10.01', '0.0000000', '10.01'],
            ['1', '0.0000001', '0.9999999'],
            ['1', '0.0000001', '0.9999999'],
            ['0', '0.0000001', '-0.0000001'],
            ['0.0000001', '0.0000001', '0'],
            ['0.0000001', '0.0000002', '-0.0000001'],
            ['0.0000000', '10.01', '-10.01'],
            ['0.0000001', '10.01', '-10.0099999'],
            ['9.999999', '9.999999', '0'],
            ['9.999999999999999999', '9.999999999999999999', '0'],
            ['9.999999999999999999', '9.999999999999999998', '0.000000000000000001'],
            [
                '9223372036854775807.9223372036854775807',
                '1.01',
                '9223372036854775806.9123372036854775807',
            ],
        ];
    }
}
