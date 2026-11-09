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
use Presta_Shop\Decimal\Operation\Addition;

class Addition_Test extends TestCase
{
    /**
     * Given two decimal numbers
     * When computing the addition operation
     * Then we should get the result of adding those numbers
     *
     * @param string $number1
     * @param string $number2
     * @param string $expected_result
     *
     * @dataProvider provide_numbers_to_add
     */
    public function test_it_adds_numbers($number1, $number2, $expected_result)
    {
        $n1 = new Decimal_Number($number1);
        $n2 = new Decimal_Number($number2);

        $operation = new Addition();
        $result1 = $operation->compute_using_bc_math($n1, $n2);
        $result2 = $operation->compute_without_bc_math($n1, $n2);

        $this->assertSame($expected_result, (string) $result1, "Failed asserting $number1 + $number2 = $expected_result (BC Math)");
        $this->assertSame($expected_result, (string) $result2, "Failed asserting $number1 + $number2 = $expected_result");
    }

    public function provide_numbers_to_add()
    {
        return [
            ['0', '0', '0'],
            ['0', '5', '5'],
            ['0', '5.1', '5.1'],
            ['5', '0', '5'],
            ['5.1', '0', '5.1'],
            ['1.234', '5', '6.234'],
            ['5', '1.234', '6.234'],
            ['10', '0.0000000', '10'],
            ['0.0000000', '10', '10'],
            ['10.01', '0.0000000', '10.01'],
            ['0.0000000', '10.01', '10.01'],
            ['0.0000001', '10.01', '10.0100001'],
            ['9.999999', '9.999999', '19.999998'],
            ['9.999999999999999999', '9.999999999999999999', '19.999999999999999998'],
            [
                '9223372036854775807.9223372036854775807',
                '1.01',
                '9223372036854775808.9323372036854775807',
            ],
            // test adding numbers to negative numbers
            ['-2', '1', '-1'],
            ['-2', '2', '0'],
            ['-2', '3', '1'],
            ['-2', '0.001', '-1.999'],
            // test it delegates to subtraction
            ['2', '-1', '1'],
            ['2', '-2', '0'],
            ['2', '-3', '-1'],
            ['1', '-0.001', '0.999'],
            ['0', '-0.001', '-0.001'],
            ['0', '-1.001', '-1.001'],
            ['-1', '-2', '-3'],
            ['100.12345567433134123236345', '-1.1', '99.02345567433134123236345'],
        ];
    }
}
