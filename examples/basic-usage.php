<?php

declare(strict_types=1);

/**
 * Example: Arbitrary-precision arithmetic with Decimal_Number.
 *
 * Unlike PHP's native floats, Decimal_Number avoids representation errors
 * that cause issues in financial calculations.
 */

use PrestaShop\Decimal\Decimal_Number;
use PrestaShop\Decimal\Operation\Rounding;

require_once __DIR__ . '/../vendor/autoload.php';

// --- 1. Construction ---
$price   = new Decimal_Number('19.99');
$tax     = new Decimal_Number('0.20');    // 20% VAT
$qty     = new Decimal_Number('3');

// --- 2. Arithmetic (all operations return new instances) ---
$subtotal = $price->times($qty);
echo "Subtotal:   {$subtotal}" . PHP_EOL;          // 59.97

$tax_amount = $subtotal->times($tax);
echo "Tax:        {$tax_amount}" . PHP_EOL;         // 11.994

$total = $subtotal->plus($tax_amount);
echo "Total:      {$total}" . PHP_EOL;              // 71.964

// --- 3. Rounding ---
echo "Rounded:    " . $total->to_precision(2, Rounding::ROUND_HALF_UP) . PHP_EOL; // 71.96

// --- 4. Comparison ---
$minimum_order = new Decimal_Number('50.00');
if ($total->is_greater_than($minimum_order)) {
    echo "Order qualifies for free shipping." . PHP_EOL;
}

// --- 5. No floating-point errors ---
$a = new Decimal_Number('0.1');
$b = new Decimal_Number('0.2');
$sum = $a->plus($b);
echo "0.1 + 0.2 = {$sum}" . PHP_EOL;   // 0.3  (not 0.30000000000000004)
