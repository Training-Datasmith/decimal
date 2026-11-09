<?php

declare(strict_types=1);

/**
 * Enforce repo-owned snake_case naming; allow upstream / third-party identifiers only via allowlist.
 */
const RENAME_CHECK_VERSION = 2;

/** @var list<string> */
const ALLOWLIST_EXACT = [
    'InvalidArgumentException',
    'PHPUnit',
    'TestCase',
    'setUpBeforeClass',
    '__toString',
    'assertSame',
    'expectException',
    'CodingStandards',
    'FilesystemIterator',
    'RecursiveDirectoryIterator',
    'RecursiveIteratorIterator',
    'Generator',
    'JSON_PRETTY_PRINT',
    'JSON_UNESCAPED_SLASHES',
    'COMPOSER_ALLOW_SUPERUSER',
    'reportUnmatchedIgnoredErrors',
    'dataProvider',
];

/**
 * Site-scoped allowlist: match only inside these URL / vendor contexts (whole-line or substring rules).
 *
 * @var list<string>
 */
const ALLOWLIST_LINE_PATTERNS = [
    '/https?:\/\/github\.com\/PrestaShop\//',
    '/https?:\/\/coveralls\.io\/repos\/github\/PrestaShop\//',
    '/https?:\/\/www\.prestashop\.com\//',
    '/https?:\/\/getcomposer\.org\//',
    '/PrestaShop\\\\CodingStandards/',
    '/@author\s+PrestaShop SA/',
    '/"name":\s*"prestashop\/decimal"/',
    '/"name":\s*"PrestaShop SA"/',
    '/phpstan\.org/',
    '/sebastianbergmann/',
];

/** @var list<string> */
const LEGACY_FORBIDDEN_LITERALS = [
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
];

/**
 * camelCase repo-owned token (at least one internal capital after a lowercase start).
 */
const CAMEL_TOKEN_PATTERN = '/(?<![A-Za-z0-9_])([a-z][a-z0-9]*(?:[A-Z][a-z0-9]+)+)(?![A-Za-z0-9_])/';

/**
 * @return list<string>
 */
function rename_scan_paths(string $root): array
{
    $paths = [
        $root . '/src',
        $root . '/tests',
        $root . '/examples',
        $root . '/README.md',
        $root . '/ARCHITECTURE.md',
        $root . '/CONTRIBUTING.md',
    ];

    $files = [];
    foreach ($paths as $path) {
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (preg_match('/\.(php|md)$/', $name) !== 1) {
                continue;
            }
            if (strpos($file->getPathname(), '/vendor/') !== false) {
                continue;
            }
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function line_is_allowlisted(string $line): bool
{
    foreach (ALLOWLIST_LINE_PATTERNS as $pattern) {
        if (preg_match($pattern, $line) === 1) {
            return true;
        }
    }

    return false;
}

function token_is_allowlisted(string $token): bool
{
    if (in_array($token, ALLOWLIST_EXACT, true)) {
        return true;
    }

    // PHP built-ins / common third-party fragments
    if (preg_match('/^(get|set|is)[A-Z]/', $token) === 1 && preg_match('/Exception$/', $token) === 1) {
        return true;
    }

    return false;
}

/**
 * @return list<string>
 */
function find_camel_violations(string $contents, string $label): array
{
    $failures = [];
    $lines = preg_split('/\R/', $contents);
    if ($lines === false) {
        return ["{$label}: cannot split lines"];
    }

    foreach ($lines as $lineNum => $line) {
        $humanLine = $lineNum + 1;
        if (line_is_allowlisted($line)) {
            continue;
        }

        foreach (LEGACY_FORBIDDEN_LITERALS as $needle) {
            if (strpos($line, $needle) !== false) {
                $failures[] = "{$label}:{$humanLine}: forbidden legacy `{$needle}`";
            }
        }

        if (preg_match('/\bfunction\s+test[A-Z]/', $line) === 1) {
            $failures[] = "{$label}:{$humanLine}: camelCase test method";
        }

        if (strpos($line, '@dataProvider') !== false) {
            continue;
        }

        if (preg_match_all(CAMEL_TOKEN_PATTERN, $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $token = $match[1];
                if (token_is_allowlisted($token)) {
                    continue;
                }
                if (strpos($line, 'http://') !== false || strpos($line, 'https://') !== false) {
                    if (preg_match('#https?://[^\s`"\')]+#', $line, $urlMatch) === 1 && strpos($urlMatch[0], $token) !== false) {
                        continue;
                    }
                }
                $failures[] = "{$label}:{$humanLine}: repo-owned camelCase `{$token}`";
            }
        }
    }

    return $failures;
}

/**
 * @return list<string>
 */
function scan_tree(string $root): array
{
    $all = [];
    foreach (rename_scan_paths($root) as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            $all[] = "Cannot read {$file}";
            continue;
        }
        $rel = str_replace($root . '/', '', $file);
        $all = array_merge($all, find_camel_violations($contents, $rel));
    }

    return $all;
}

/**
 * Built-in probes: must fail on synthetic bad snippets and pass on synthetic good snippet.
 */
function run_builtin_probes(): ?string
{
    $bad_markdown = "public Decimal_Number::dividedBy ( Decimal_Number \$x )\n";
    $bad_docblock = "/**\n * @param int \$fooBar value\n */\n";
    $good = "public Decimal_Number::divided_by ( Decimal_Number \$x )\n/**\n * @param int \$foo_bar\n */\n";

    $badFailures = array_merge(
      find_camel_violations($bad_markdown, 'probe-md'),
      find_camel_violations($bad_docblock, 'probe-doc')
  );
    if ($badFailures === []) {
        return 'rename-check: self-probe expected dividedBy ( and camelCase @param to fail';
    }

    $hasDividedBy = false;
    $hasDocParam = false;
    foreach ($badFailures as $f) {
        if (strpos($f, 'dividedBy') !== false || strpos($f, '`dividedBy`') !== false) {
            $hasDividedBy = true;
        }
        if (strpos($f, 'fooBar') !== false) {
            $hasDocParam = true;
        }
    }
    if (!$hasDividedBy || !$hasDocParam) {
        return 'rename-check: self-probe did not flag both dividedBy and fooBar';
    }

    $goodFailures = find_camel_violations($good, 'probe-ok');
    if ($goodFailures !== []) {
        return 'rename-check: self-probe false positive on clean snippet: ' . implode('; ', $goodFailures);
    }

    return null;
}

$probeError = run_builtin_probes();
if ($probeError !== null) {
    fwrite(STDERR, $probeError . "\n");
    exit(1);
}

$root = dirname(__DIR__);
$failures = scan_tree($root);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "rename-check: no old repo-owned names remain\n");
exit(0);
