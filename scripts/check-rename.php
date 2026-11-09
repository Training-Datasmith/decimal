<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    $root . '/src',
    $root . '/tests',
    $root . '/examples',
    $root . '/composer.json',
    $root . '/README.md',
    $root . '/ARCHITECTURE.md',
];

$forbidden_literals = [
    'PrestaShop\\Decimal',
    'DecimalNumber',
    'DivisionByZeroException',
    'MagnitudeChange',
    'getIntegerPart',
    'getFractionalPart',
    'getSign(',
    'getExponent(',
    'getCoefficient(',
    'toPrecision(',
    'dividedBy(',
    'equalsZero(',
    'isGreaterThan(',
    'isLowerThan(',
    'computeUsingBcMath',
    'computeWithoutBcMath',
    'compareUsingBcMath',
    'compareWithoutBcMath',
    'integerPart',
    'fractionalPart',
    'exponentPart',
    'exponentSign',
];

$allow_patterns = [
    '/PrestaShop\\\\CodingStandards/',
    '/PrestaShop SA/',
    '/github\\.com\\/PrestaShop/',
    '/coveralls\\.io\\/repos\\/github\\/PrestaShop/',
    '/prestashop\\.com/',
    '/name": "prestashop\/decimal"/',
    '/"name": "PrestaShop SA"/',
];

$camel_test_pattern = '/\bfunction\s+test[A-Z]/';
$camel_repo_method = '/\bfunction\s+(provide|get|round)[A-Z]/';

$failures = [];

foreach ($paths as $path) {
    if (is_file($path)) {
        $files = [$path];
    } elseif (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (preg_match('/\.(php|md|json)$/', $name) !== 1) {
                continue;
            }
            $files[] = $file->getPathname();
        }
    } else {
        continue;
    }

    foreach ($files as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            $failures[] = "Cannot read {$file}";
            continue;
        }

        foreach ($forbidden_literals as $needle) {
            if (strpos($contents, $needle) === false) {
                continue;
            }
            $allowed = false;
            foreach ($allow_patterns as $pattern) {
                if (preg_match($pattern, $contents) === 1 && strpos($file, 'composer.json') !== false) {
                    $allowed = true;
                }
            }
            if ($allowed) {
                continue;
            }
            $failures[] = "{$file}: forbidden legacy identifier `{$needle}`";
        }

        if (preg_match('/\.php$/', $file) === 1) {
            if (preg_match($camel_test_pattern, $contents) === 1) {
                $failures[] = "{$file}: repo-owned camelCase test method remains";
            }
            if (preg_match($camel_repo_method, $contents) === 1) {
                $failures[] = "{$file}: repo-owned camelCase helper/provider method remains";
            }
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "rename-check: no old repo-owned names remain\n");
exit(0);
