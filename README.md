# Decimal

[![Build status](https://github.com/PrestaShop/decimal/actions/workflows/php.yml/badge.svg)](https://github.com/PrestaShop/decimal/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/PrestaShop/decimal/badge.svg)](https://coveralls.io/github/PrestaShop/decimal)
[![Total Downloads](https://img.shields.io/packagist/dt/prestashop/decimal.svg?style=flat-square)](https://packagist.org/packages/prestashop/decimal)

An object-oriented [BC Math extension](http://php.net/manual/en/book.bc.php) wrapper/shim.

**Decimal** offers a stateless, fluent object-oriented implementation of basic arbitrary-precision arithmetic, using BC Math if available.

You can find out more about floating point precision [here](http://php.net/float).

Example:
```php
use Presta_Shop\Decimal\Decimal_Number;
use Presta_Shop\Decimal\Operation\Rounding;

echo (new Decimal_Number('0.1'))
    ->plus(new Decimal_Number('0.7'))
    ->times(new Decimal_Number('10'))
    ->round(0, Rounding::ROUND_FLOOR)
  
// echoes '8'
```

## Install

Via Composer

``` bash
$ composer require prestashop/decimal
```

## Usage reference

Quick links:
- [Instantiation](#instantiation)
- [Addition](#addition)
- [Subtraction](#subtraction)
- [Multiplication](#multiplication)
- [Division](#division)
- [Comparison](#comparison)
- [Fixed precision](#fixed-precision)
- [Rounding](#rounding)
- [Dot shifting](#dot-shifting)
- [Useful methods](#useful-methods)

### Instantiation
Creates a new Decimal number.
```php
public __construct ( string $number [, int $exponent = null ] ): Decimal_Number
```
There are two ways to instantiate a Decimal\Decimal_Number:
``` php
// create a number from string
$number = new Presta_Shop\Decimal\Decimal_Number('123.456');
echo $number; // echoes '123.456'
```
``` php
// exponent notation
$number = new Presta_Shop\Decimal\Decimal_Number('123456', -3);
echo $number; // echoes '123.456'
```

### Addition
Returns the computed result of adding another number to the current one.
```php
public Decimal_Number::plus ( Decimal_Number $addend ): Decimal_Number
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('123.456');
$b = new Presta_Shop\Decimal\Decimal_Number('654.321');

echo $a->plus($b); // echoes '777.777'
```

### Subtraction
Returns the computed result of subtracting another number to the current one.
```php
public Decimal_Number::minus ( Decimal_Number $subtrahend ): Decimal_Number
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('777.777');
$b = new Presta_Shop\Decimal\Decimal_Number('654.321');

echo $a->minus($b); // echoes '123.456'
```

### Multiplication
Returns the computed result of multiplying the current number with another one.
```php
public Decimal_Number::times ( Decimal_Number $factor ): Decimal_Number
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('777.777');
$b = new Presta_Shop\Decimal\Decimal_Number('654.321');

echo $a->times($b); // echoes '508915.824417'
```

### Division
Returns the computed result of dividing  the current number by another one, with up to a certain number of decimal positions (6 by default).
```php
public Decimal_Number::dividedBy ( Decimal_Number $divisor [, int $precision = Operation\Division::DEFAULT_PRECISION ] )
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('777.777');
$b = new Presta_Shop\Decimal\Decimal_Number('654.321');

echo $a->divided_by($b, 0);  // echoes '1'
echo $a->divided_by($b, 5);  // echoes '1.18867'
echo $a->divided_by($b, 10); // echoes '1.1886780341'
echo $a->divided_by($b, 15); // echoes '1.188678034175886'
```

### Comparison
Returns the result of the comparison assertion.
```php
$a = new Presta_Shop\Decimal\Decimal_Number('777.777');
$b = new Presta_Shop\Decimal\Decimal_Number('654.321');

$a->equals($b);                // returns false
$a->is_lower_than($b);           // returns false
$a->is_lower_or_equal_than($b);    // returns false
$a->is_greater_than($b);         // returns true
$a->is_greater_or_equal_than($b);  // returns true

// shortcut methods
$a->equals_zero();               // returns false
$a->is_lower_than_zero();          // returns false 
$a->is_lower_or_equal_than_zero();   // returns false
$a->is_greater_than_zero();        // returns true
$a->is_greater_or_equal_than_zero(); // returns true
```

### Fixed precision
Returns the number as a string, optionally rounded, with an exact number of decimal positions.
```php
public Decimal_Number::toPrecision ( int $precision [, string $roundingMode = Rounding::ROUND_TRUNCATE ] ): string
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('123.456');
$a = new Presta_Shop\Decimal\Decimal_Number('-123.456');

// truncate / pad
$a->to_precision(0); // '123'
$a->to_precision(1); // '123.4'
$a->to_precision(2); // '123.45'
$a->to_precision(3); // '123.456'
$a->to_precision(4); // '123.4560'
$b->to_precision(0); // '-123'
$b->to_precision(1); // '-123.4'
$b->to_precision(2); // '-123.45'
$b->to_precision(3); // '-123.456'
$b->to_precision(4); // '-123.4560'

// ceil (round up)
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '124'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '123.5'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '123.46'
$b->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '-122'
$b->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '-123.3'
$b->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_CEIL); // '-123.44'

// floor (round down)
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '123.4'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '123.45'
$b->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '-124'
$b->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '-123.5'
$b->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_FLOOR); // '-123.46'

// half-up (symmetric half-up)
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '123.5'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '123.46'
$b->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '-123'
$b->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '-123.5'
$b->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_UP); // '-123.46'

// half-down (symmetric half-down)
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '123.4'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '123.46'
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '-123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '-123.4'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_DOWN); // '-123.46'

// half-even
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '123.4'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '123.46'
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '-123'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '-123.4'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '-123.46'

$a = new Presta_Shop\Decimal\Decimal_Number('1.1525354556575859505');
$a->to_precision(0, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1'
$a->to_precision(1, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.2'
$a->to_precision(2, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.15'
$a->to_precision(3, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.152'
$a->to_precision(4, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.1525'
$a->to_precision(5, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.15255'
$a->to_precision(6, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.152535'
$a->to_precision(7, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.1525354'
$a->to_precision(8, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.15253546'
$a->to_precision(9, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN);  // '1.152535456'
$a->to_precision(10, Presta_Shop\Decimal\Operation\Rounding::ROUND_HALF_EVEN); // '1.1525354556'
```

### Rounding
Rounding behaves like `toPrecision`, but provides "up to" a certain number of decimal positions
(it does not add trailing zeroes).
```php
public Decimal_Number::round ( int $maxDecimals [, string $roundingMode = Rounding::ROUND_TRUNCATE ] ): string
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('123.456');
$a = new Presta_Shop\Decimal\Decimal_Number('-123.456');

// truncate / pad
$a->round(0); // '123'
$a->round(1); // '123.4'
$a->round(2); // '123.45'
$a->round(3); // '123.456'
$a->round(4); // '123.456'
$b->round(0); // '-123'
$b->round(1); // '-123.4'
$b->round(2); // '-123.45'
$b->round(3); // '-123.456'
$b->round(4); // '-123.456'
```

### Dot shifting
Creates a new copy of this number multiplied by 10^exponent
```php
public Decimal_Number::toMagnitude ( int $exponent ): Decimal_Number
```
Examples:
```php
$a = new Presta_Shop\Decimal\Decimal_Number('123.456789');

// shift 3 digits to the left
$a->to_magnitude(-3); // 0.123456789

// shift 3 digits to the right
$a->to_magnitude(3); // 123456.789
```

### Useful methods
```php
$number = new Presta_Shop\Decimal\Decimal_Number('123.45');
$number->get_integer_part();    // '123'
$number->get_fractional_part(); // '45'
$number->getPrecision();      // '2' (number of decimals)
$number->get_sign();           // '' ('-' if the number was negative)
$number->get_exponent();       // '2' (always positive)
$number->get_coefficient();    // '123456'
$number->is_positive();        // true
$number->is_negative();        // false
$number->invert();            // new Decimal\Decimal_Number('-123.45')
```

## Testing

``` bash
$ composer install
$ vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [All Contributors](https://github.com/prestashop/decimal/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
