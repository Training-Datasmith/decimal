# Architecture: decimal

## Purpose

An arbitrary-precision decimal arithmetic library for PHP. It avoids floating-point representation errors by storing numbers as an integer coefficient plus a non-negative exponent (scientific notation). All arithmetic operations produce new `Decimal_Number` instances — values are immutable.

## Directory Structure

```
src/
  Decimal_Number.php               — Primary value object: immutable arbitrary-precision number
  Number.php                       — Alias / base class (kept for backward compatibility)
  Builder.php                      — Internal parser: converts decimal string → coefficient + exponent
  Operation/
    Addition.php                   — add(a, b) → Decimal_Number
    Subtraction.php                — subtract(a, b) → Decimal_Number
    Multiplication.php             — multiply(a, b) → Decimal_Number
    Division.php                   — divide(a, b, precision) → Decimal_Number
    Comparison.php                 — compare(a, b) → -1|0|1
    Magnitude_Change.php           — multiply by 10^n (shift decimal point)
    Rounding.php                   — truncate / half-up / half-even rounding
  Exception/
    Division_By_Zero_Exception.php — Thrown when divisor is zero

tests/
  Decimal_Number_Test.php          — Unit tests for the value object
  Operation/                       — Tests for each arithmetic operation
```

## Key Design Decisions

- **Scientific notation storage** — `coefficient × 10^(-exponent)` where both values are strings/ints. Eliminates IEEE 754 drift entirely.
- **Immutability** — every arithmetic method returns a new instance; no mutation after construction.
- **Trailing-zero normalisation** — the constructor strips trailing fractional zeros so `1.500` equals `1.5` internally, keeping comparisons simple.
- **Explicit precision on division** — `divided_by()` requires callers to specify max decimals to avoid infinite expansion (e.g., 1/3).

## Extension Points

- Add new operations by creating classes in `Operation/` following the `compute()` convention.
- Subclass `Decimal_Number` (it uses `new static(...)` in `invert()` and `to_magnitude()`) to carry additional domain metadata.

## Dependency Flow

```
Decimal_Number
  ├── Builder::parse_number(string) → Decimal_Number  [constructor only]
  └── Operation\* :: compute(Decimal_Number, ...) → Decimal_Number
```
