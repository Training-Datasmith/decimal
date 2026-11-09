#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
UPSTREAM_SHA="9308681"
OUTPUT_FILE="${ROOT}/scripts/upstream-baseline.txt"
BASELINE_DIR="$(mktemp -d)"

cleanup() {
    rm -rf "${BASELINE_DIR}"
}
trap cleanup EXIT

git -C "${ROOT}" archive "${UPSTREAM_SHA}" | tar -x -C "${BASELINE_DIR}"

php -r '
$path = $argv[1];
$data = json_decode(file_get_contents($path), true);
$data["require-dev"]["phpunit/phpunit"] = "^8.5.52";
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
' "${BASELINE_DIR}/composer.json"

cp "${ROOT}/phpunit.xml.dist" "${BASELINE_DIR}/phpunit.xml.dist"
php -r '
$path = $argv[1];
$xml = file_get_contents($path);
$xml = preg_replace("#\\s*<logging>[\\s\\S]*?</logging>\\s*#", "", $xml);
$xml = preg_replace("#\\s*<filter>[\\s\\S]*?</filter>\\s*#", "", $xml);
file_put_contents($path, $xml);
' "${BASELINE_DIR}/phpunit.xml.dist"

cd "${BASELINE_DIR}"
export COMPOSER_ALLOW_SUPERUSER=1
git config --global --add safe.directory "${BASELINE_DIR}"
php "${ROOT}/.system-test/composer.phar" update --no-interaction --prefer-dist --no-progress
vendor/bin/phpunit --colors=never > "${BASELINE_DIR}/phpunit.out" 2>&1
phpunit_status=$?
cat "${BASELINE_DIR}/phpunit.out"
if [[ ${phpunit_status} -ne 0 ]]; then
    exit "${phpunit_status}"
fi

ok_line="$(grep -E '^OK \([0-9]+ tests?, [0-9]+ assertions\)$' "${BASELINE_DIR}/phpunit.out" | tail -n 1)"
if [[ -z "${ok_line}" ]]; then
    echo 'upstream baseline: missing OK line' >&2
    exit 1
fi

printf '%s\n' "${ok_line}" > "${OUTPUT_FILE}"
echo "upstream baseline recorded: ${ok_line}"
