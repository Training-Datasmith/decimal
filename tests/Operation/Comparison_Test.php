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
use Presta_Shop\Decimal\Operation\Comparison;

class Comparison_Test extends TestCase
{
    /**
     * @var Decimal_Number
     */
    private static $zero;

    public static function setUpBeforeClass(): void
    {
        static::$zero = new Decimal_Number('0');
    }

    /**
     * Given two numbers
     * When comparing them
     * Then we should get
     * - 1 if a > b
     * - -1 if b > a
     * - 0 if a == b
     *
     * @param string $a
     * @param string $b
     * @param int $expected
     *
     * @dataProvider provide_compare_test_cases
     */
    public function test_it_compares_numbers($a, $b, $expected)
    {
        $comparison = new Comparison();

        $result1 = $comparison->compare_using_bc_math(new Decimal_Number($a), new Decimal_Number($b));
        $result2 = $comparison->compare_without_bc_math(new Decimal_Number($a), new Decimal_Number($b));

        $this->assertSame($expected, $result1, 'Failed assertion (BC Math)');
        $this->assertSame($expected, $result2, 'Failed assertion');
    }

    public function provide_compare_test_cases()
    {
        return [
            // a is greater
            'greater 1' => ['1', '0', 1],
            'greater 2' => ['1.0', '0', 1],
            'greater 3' => ['1.01', '1.0', 1],
            'greater 4' => ['1.0000000000000000000000001', '1.0', 1],
            'greater 5' => ['10', '001', 1],
            'greater 6' => ['10', '-10', 1],
            'greater 7' => ['10', '-100', 1],
            'greater 8' => ['100', '10', 1],
            'greater 9' => ['-1', '-2', 1],
            'greater 10' => ['-1', '-0000002', 1],
            'greater 11' => ['-1', '-1.0000000001', 1],
            // a is equal
            'equal 1' => ['1', '01', 0],
            'equal 2' => ['0.1', '0000.1000000000000', 0],
            // a is lower
            'lower 1' => ['0', '1', -1],
            'lower 2' => ['-1', '0', -1],
            'lower 3' => ['-1', '0.0001', -1],
            'lower 4' => ['-2', '-1', -1],
            'lower 5' => ['-02', '-1', -1],
            'lower 6' => ['-2', '-01', -1],
            'lower 8' => ['10', '100', -1],
            'lower 9' => ['-1.000001', '-1', -1],
            'lower 10' => ['-1000.000001', '-10.0001', -1],
        ];
    }

    /**
     * Given a number
     * It should detect if it equals zero
     *
     * @param string $number
     * @param bool $expected
     *
     * @dataProvider provide_equals_zero_tests
     */
    public function test_it_detects_equals_zero($number, $expected)
    {
        $n = new Decimal_Number($number);

        $this->assertSame(
            $expected,
            $n->equals_zero(),
            sprintf('Failed to assert that %s %s equal to zero', $number, $this->get_is_verb($expected))
        );

        // double check
        $this->assertSame(
            $expected,
            $n->equals(static::$zero),
            sprintf('Failed to assert that %s %s equal to Number zero', $number, $this->get_is_verb($expected))
        );
    }

    public function provide_equals_zero_tests()
    {
        return [
            ['0', true],
            ['000000', true],
            ['0.0000', true],
            ['-0.0000', true],
            ['0.0001', false],
            ['-0.0001', false],
            ['10', false],
            ['10.0', false],
            ['10.000001', false],
            ['10.100001', false],
            ['-10', false],
            ['-10.0', false],
            ['-10.000001', false],
            ['-10.100001', false],
        ];
    }

    /**
     * Given a number
     * It should detect if it's greater than zero
     *
     * @param string $number
     * @param bool $expected
     *
     * @dataProvider provide_greater_than_zero_tests
     */
    public function test_it_detects_greater_than_zero($number, $expected)
    {
        $n = new Decimal_Number($number);

        $this->assertSame(
            $expected,
            $n->is_greater_than_zero(),
            sprintf('Failed to assert that %s %s greater than zero', $number, $this->get_is_verb($expected))
        );

        // double check
        $this->assertSame(
            $expected,
            $n->is_greater_than(static::$zero),
            sprintf('Failed to assert that %s %s grater to Number zero', $number, $this->get_is_verb($expected))
        );
    }

    public function provide_greater_than_zero_tests()
    {
        return [
            ['0', false],
            ['000000', false],
            ['0.0000', false],
            ['-0.0000', false],
            ['0.0001', true],
            ['-0.0001', false],
            ['10', true],
            ['10.0', true],
            ['10.000001', true],
            ['10.100001', true],
            ['-10', false],
            ['-10.0', false],
            ['-10.000001', false],
            ['-10.100001', false],
        ];
    }

    /**
     * Given a number
     * It should detect if it's greater than zero
     *
     * @param string $number
     * @param bool $expected
     *
     * @dataProvider provide_greater_or_equal_than_zero_tests
     */
    public function test_it_detects_greater_or_equal_than_zero($number, $expected)
    {
        $n = new Decimal_Number($number);

        $this->assertSame(
            $expected,
            $n->is_greater_or_equal_than_zero(),
            sprintf('Failed to assert that %s %s greater or equal than zero', $number, $this->get_is_verb($expected))
        );

        // double check
        $this->assertSame(
            $expected,
            $n->is_greater_or_equal_than(static::$zero),
            sprintf('Failed to assert that %s %s greater or equal to Number zero', $number, $this->get_is_verb($expected))
        );
    }

    public function provide_greater_or_equal_than_zero_tests()
    {
        return [
            ['0', true],
            ['000000', true],
            ['0.0000', true],
            ['-0.0000', true],
            ['0.0001', true],
            ['-0.0001', false],
            ['10', true],
            ['10.0', true],
            ['10.000001', true],
            ['10.100001', true],
            ['-10', false],
            ['-10.0', false],
            ['-10.000001', false],
            ['-10.100001', false],
        ];
    }

    /**
     * Given a number
     * It should detect if it's lower than zero
     *
     * @param string $number
     * @param bool $expected
     *
     * @dataProvider provide_lower_than_zero_tests
     */
    public function test_it_detects_lower_than_zero($number, $expected)
    {
        $n = new Decimal_Number($number);

        $this->assertSame(
            $expected,
            $n->is_lower_than_zero(),
            sprintf('Failed to assert that %s %s lower than zero', $number, $this->get_is_verb($expected))
        );

        // double check
        $this->assertSame(
            $expected,
            $n->is_lower_than(static::$zero),
            sprintf('Failed to assert that %s %s lower to Number zero', $number, $this->get_is_verb($expected))
        );
    }

    public function provide_lower_than_zero_tests()
    {
        return [
            ['0', false],
            ['000000', false],
            ['0.0000', false],
            ['-0.0000', false],
            ['0.0001', false],
            ['-0.0001', true],
            ['10', false],
            ['10.0', false],
            ['10.000001', false],
            ['10.100001', false],
            ['-10', true],
            ['-10.0', true],
            ['-10.000001', true],
            ['-10.100001', true],
        ];
    }

    /**
     * Given a number
     * It should detect if it's lower than zero
     *
     * @param string $number
     * @param bool $expected
     *
     * @dataProvider provide_lower_or_equal_than_zero_tests
     */
    public function test_it_detects_lower_or_equal_than_zero($number, $expected)
    {
        $n = new Decimal_Number($number);

        $this->assertSame(
            $expected,
            $n->is_lower_or_equal_than_zero(),
            sprintf('Failed to assert that %s %s lower or equal than zero', $number, $this->get_is_verb($expected))
        );

        // double check
        $this->assertSame(
            $expected,
            $n->is_lower_or_equal_than(static::$zero),
            sprintf('Failed to assert that %s %s lower or equal to Number zero', $number, $this->get_is_verb($expected))
        );
    }

    public function provide_lower_or_equal_than_zero_tests()
    {
        return [
            ['0', true],
            ['000000', true],
            ['0.0000', true],
            ['-0.0000', true],
            ['0.0001', false],
            ['-0.0001', true],
            ['10', false],
            ['10.0', false],
            ['10.000001', false],
            ['10.100001', false],
            ['-10', true],
            ['-10.0', true],
            ['-10.000001', true],
            ['-10.100001', true],
        ];
    }

    /**
     * @param bool $assertion
     *
     * @return string
     */
    private function get_is_verb($assertion)
    {
        return ($assertion) ? 'is' : 'is NOT';
    }
}
