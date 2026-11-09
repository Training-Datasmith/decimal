<?php

declare(strict_types=1);

/**
 * Verify Packagist gap packages are not installed from Training-Datasmith index tips.
 * Org cluster aliases: none (empty expected map).
 */
const INDEX_PHPUNIT_REF = 'a37d66fabf01af0eedabffe271b70dbe71723001';
const INDEX_PHPSTAN_REF = '63f848a4b1dbe2f2cb87acb3ff32596661e45417';

/** @var array<string, array{reference: string, alias: string}> */
const ORG_EXPECTED = [];

/**
 * @param array<string, array<string, mixed>> $versions
 * @param list<array<string, mixed>> $json_packages
 * @param array<string, array{reference: string, alias: string}> $expected
 *
 * @return list<string>
 */
function verify_pins(array $versions, array $json_packages, array $expected): array
{
    $failures = [];

    $json_by_name = [];
    foreach ($json_packages as $package) {
        if (!isset($package['name'], $package['source']['reference'])) {
            continue;
        }
        $json_by_name[$package['name']] = strtolower((string) $package['source']['reference']);
    }

    foreach ($expected as $name => $want) {
        if (!isset($versions[$name])) {
            $failures[] = sprintf('installed.php: %s is not installed', $name);
            continue;
        }

        $pkg = $versions[$name];
        $aliases = $pkg['aliases'] ?? [];
        if (!in_array($want['alias'], $aliases, true)) {
            $failures[] = sprintf(
                'installed.php: %s alias mismatch (expected %s in %s)',
                $name,
                $want['alias'],
                json_encode($aliases)
            );
        }

        $reference = $pkg['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            $failures[] = sprintf('installed.php: %s has no reference', $name);
        } elseif (!hash_equals(strtolower($want['reference']), strtolower($reference))) {
            $failures[] = sprintf(
                'installed.php: %s reference mismatch (expected %s, got %s)',
                $name,
                $want['reference'],
                $reference
            );
        }

        if (!isset($json_by_name[$name])) {
            $failures[] = sprintf('installed.json: %s is not installed', $name);
            continue;
        }

        if (!hash_equals(strtolower($want['reference']), $json_by_name[$name])) {
            $failures[] = sprintf(
                'installed.json: %s source.reference mismatch (expected %s, got %s)',
                $name,
                $want['reference'],
                $json_by_name[$name]
            );
        }
    }

    return $failures;
}

/**
 * @param array<string, array<string, mixed>> $versions
 * @param list<array<string, mixed>> $json_packages
 *
 * @return list<string>
 */
function verify_packagist_gaps(array $versions, array $json_packages): array
{
    $failures = [];
    $json_by_name = [];
    foreach ($json_packages as $package) {
        if (!isset($package['name'])) {
            continue;
        }
        $json_by_name[$package['name']] = $package;
    }

    foreach (['phpunit/phpunit' => INDEX_PHPUNIT_REF, 'phpstan/phpstan' => INDEX_PHPSTAN_REF] as $name => $forbidden_ref) {
        if (!isset($versions[$name])) {
            $failures[] = sprintf('installed.php: %s is not installed', $name);
            continue;
        }

        $reference = $versions[$name]['reference'] ?? '';
        if (!is_string($reference)) {
            $failures[] = sprintf('installed.php: %s has no reference', $name);
        } elseif (0 === strcasecmp($reference, $forbidden_ref)) {
            $failures[] = sprintf('installed.php: %s is pinned to forbidden index tip %s', $name, $forbidden_ref);
        }

        if (!isset($json_by_name[$name]['source']['reference'])) {
            $failures[] = sprintf('installed.json: %s has no source.reference', $name);
        } elseif (0 === strcasecmp((string) $json_by_name[$name]['source']['reference'], $forbidden_ref)) {
            $failures[] = sprintf('installed.json: %s is pinned to forbidden index tip %s', $name, $forbidden_ref);
        }

        $version = $versions[$name]['pretty_version'] ?? ($json_by_name[$name]['version'] ?? '');
        if ($name === 'phpunit/phpunit') {
            if (!preg_match('/^8\.5\.(5[2-9]|[6-9]\d|\d{3,})/', (string) $version)) {
                $failures[] = sprintf('installed.php: phpunit/phpunit version %s is not >= 8.5.52', $version);
            }
        }
        if ($name === 'phpstan/phpstan') {
            if (!preg_match('/^0\.12\./', (string) $version)) {
                $failures[] = sprintf('installed.php: phpstan/phpstan version %s is not 0.12.x', $version);
            }
        }
    }

    return $failures;
}

/**
 * @return array{0: array<string, array<string, mixed>>, 1: list<array<string, mixed>>}
 */
function load_vendor_state(string $root): array
{
    $installed_php = $root . '/vendor/composer/installed.php';
    $installed_json = $root . '/vendor/composer/installed.json';

    if (!is_readable($installed_php) || !is_readable($installed_json)) {
        fwrite(STDERR, "check-cluster-pins: run composer update first\n");
        exit(1);
    }

    /** @var array{versions: array<string, array<string, mixed>>} $installed */
    $installed = require $installed_php;
    $raw = file_get_contents($installed_json);
    if ($raw === false) {
        fwrite(STDERR, "check-cluster-pins: cannot read installed.json\n");
        exit(1);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        fwrite(STDERR, "check-cluster-pins: invalid installed.json\n");
        exit(1);
    }

    $packages = array_merge($data['packages'] ?? [], $data['packages-dev'] ?? []);

    return [$installed['versions'] ?? [], $packages];
}

function file_sha256(string $path): string
{
    return hash_file('sha256', $path);
}

$root = dirname(__DIR__);
[$versions, $json_packages] = load_vendor_state($root);

$installed_php_path = $root . '/vendor/composer/installed.php';
$installed_json_path = $root . '/vendor/composer/installed.json';
$hash_php_before = file_sha256($installed_php_path);
$hash_json_before = file_sha256($installed_json_path);

$synthetic_ref = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
$demo_expected = [
    'demo/cluster' => [
        'reference' => $synthetic_ref,
        'alias' => '9.9.9',
    ],
];

$demo_versions = $versions;
$demo_versions['demo/cluster'] = [
    'reference' => '0000000000000000000000000000000000000000',
    'aliases' => ['9.9.9'],
    'pretty_version' => 'dev-demo',
];

$demo_json = $json_packages;
$demo_json[] = [
    'name' => 'demo/cluster',
    'version' => 'dev-demo',
    'source' => [
        'type' => 'git',
        'url' => 'https://example.test/demo.git',
        'reference' => $synthetic_ref,
    ],
];

$demo_failures = verify_pins($demo_versions, $demo_json, $demo_expected);
$demo1_ok = false;
foreach ($demo_failures as $failure) {
    if (strpos($failure, 'installed.php:') === 0) {
        $demo1_ok = true;
        break;
    }
}
if (!$demo1_ok) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA demo 1 did not fail on installed.php\n");
    exit(1);
}
fwrite(STDOUT, "check-cluster-pins: wrong-SHA demo rejected installed.php reference\n");

$demo_versions2 = $versions;
$demo_versions2['demo/cluster'] = [
    'reference' => $synthetic_ref,
    'aliases' => ['9.9.9'],
    'pretty_version' => 'dev-demo',
];
$demo_json2 = $json_packages;
$demo_json2[] = [
    'name' => 'demo/cluster',
    'version' => 'dev-demo',
    'source' => [
        'type' => 'git',
        'url' => 'https://example.test/demo.git',
        'reference' => 'ffffffffffffffffffffffffffffffffffffffff',
    ],
];

$demo_failures2 = verify_pins($demo_versions2, $demo_json2, $demo_expected);
$demo2_ok = false;
foreach ($demo_failures2 as $failure) {
    if (strpos($failure, 'installed.json:') === 0) {
        $demo2_ok = true;
        break;
    }
}
if (!$demo2_ok) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA demo 2 did not fail on installed.json\n");
    exit(1);
}
fwrite(STDOUT, "check-cluster-pins: wrong-SHA demo rejected installed.json source.reference\n");

$hash_php_after = file_sha256($installed_php_path);
$hash_json_after = file_sha256($installed_json_path);
if ($hash_php_before !== $hash_php_after || $hash_json_before !== $hash_json_after) {
    fwrite(STDERR, "check-cluster-pins: vendor composer manifests changed during demo\n");
    exit(1);
}
fwrite(STDOUT, sprintf(
    "check-cluster-pins: vendor/composer/installed.php sha256 unchanged (%s)\n",
    $hash_php_after
));
fwrite(STDOUT, sprintf(
    "check-cluster-pins: vendor/composer/installed.json sha256 unchanged (%s)\n",
    $hash_json_after
));

$failures = array_merge(
    verify_pins($versions, $json_packages, ORG_EXPECTED),
    verify_packagist_gaps($versions, $json_packages)
);
if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: org aliases 0; Packagist phpunit and phpstan are not index tips\n");
exit(0);
