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
use Presta_Shop\Decimal\Operation\Multiplication;

class Multiplication_Test extends TestCase
{
    /**
     * Given two decimal numbers
     * When computing the multiplication operation
     * Then we should get the result of multiplying those numbers
     *
     * @param string $number1
     * @param string $number2
     * @param string $expected_result
     *
     * @dataProvider provide_numbers_to_multiply
     */
    public function test_it_multiplies_numbers($number1, $number2, $expected_result)
    {
        $n1 = new Decimal_Number($number1);
        $n2 = new Decimal_Number($number2);

        $operation = new Multiplication();
        $result1 = $operation->compute_using_bc_math($n1, $n2);
        $result2 = $operation->compute_without_bc_math($n1, $n2);

        $this->assertSame($expected_result, (string) $result1, "Failed asserting $number1 * $number2 = $expected_result (BC Math)");
        $this->assertSame($expected_result, (string) $result2, "Failed asserting $number1 * $number2 = $expected_result");
    }

    public function provide_numbers_to_multiply()
    {
        return [
            // integer
            'integer 1' => ['1', '1', '1'],
            'integer 2' => ['1', '0', '0'],
            'integer 3' => ['99999999990', '1', '99999999990'],
            'integer 4' => ['1', '99999999990', '99999999990'],
            'integer 5' => ['99999999990', '0', '0'],
            'integer 6' => ['99999999990', '2', '199999999980'],
            'integer 7' => ['99999999990', '10', '999999999900'],
            'integer 8' => ['123456789', '123456789', '15241578750190521'],
            'integer 9' => ['123456789', '-1', '-123456789'],
            'integer 10' => ['-123456789', '-1', '123456789'],
            'integer 11' => ['-123456789', '1', '-123456789'],
            'integer 12' => ['123', '11234667', '1381864041'],
            // decimals
            'decimal 1' => ['99999999990', '0.1', '9999999999'],
            'decimal 2' => ['99999999990', '0.0001', '9999999.999'],
            'decimal 3' => ['99999999990', '0.0002', '19999999.998'],
            'decimal 4' => ['1234.56789', '1234.56789', '1524157.8750190521'],
        ];
    }
}
