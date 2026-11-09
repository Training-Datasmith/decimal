#!/usr/bin/env bash
set -euo pipefail

export COMPOSER_ALLOW_SUPERUSER=1

git config --global --add safe.directory /app

strip_ansi() {
    sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g'
}

fail_deprecation_output() {
    local label="$1"
    local file="$2"
    local stripped
    stripped="$(mktemp)"
    strip_ansi < "${file}" > "${stripped}"
    if grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >/dev/null; then
        echo "${label}: forbidden PHP message in output" >&2
        grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >&2 || true
        rm -f "${stripped}"
        exit 1
    fi
    rm -f "${stripped}"
}

require_line() {
    local label="$1"
    local file="$2"
    local pattern="$3"
    local stripped
    stripped="$(mktemp)"
    strip_ansi < "${file}" > "${stripped}"
    if ! grep -E "${pattern}" "${stripped}" >/dev/null; then
        echo "${label}: missing required line matching ${pattern}" >&2
        cat "${stripped}" >&2
        rm -f "${stripped}"
        exit 1
    fi
    grep -E "${pattern}" "${stripped}" | tail -n 1
    rm -f "${stripped}"
}

php -v | tee /tmp/php-version.txt

COMPOSER_URL="https://getcomposer.org/download/2.8.10/composer.phar"
COMPOSER_SHA256="28dbb6bd8bef31479c7985b774c130a8bda37dbe63c35b56f6cb6bc377427573"
mkdir -p /app/.system-test
if [[ ! -f /app/.system-test/composer.phar ]]; then
    curl -fsSL -o /app/.system-test/composer.phar "${COMPOSER_URL}"
    echo "${COMPOSER_SHA256}  /app/.system-test/composer.phar" | sha256sum -c -
fi
php /app/.system-test/composer.phar --version

composer_out="$(mktemp)"
set +e
php /app/.system-test/composer.phar update --no-interaction --prefer-dist --no-progress > "${composer_out}" 2>&1
composer_status=$?
set -e
cat "${composer_out}"
fail_deprecation_output "composer update" "${composer_out}"
if [[ ${composer_status} -ne 0 ]]; then
    rm -f "${composer_out}"
    exit "${composer_status}"
fi
rm -f "${composer_out}"

validate_out="$(mktemp)"
set +e
php /app/.system-test/composer.phar validate --strict > "${validate_out}" 2>&1
validate_status=$?
set -e
cat "${validate_out}"
fail_deprecation_output "composer validate" "${validate_out}"
if [[ ${validate_status} -ne 0 ]]; then
    exit "${validate_status}"
fi
require_line "composer validate" "${validate_out}" '^\./composer\.json is valid$'
rm -f "${validate_out}"

audit_out="$(mktemp)"
set +e
php /app/.system-test/composer.phar audit --abandoned=report > "${audit_out}" 2>&1
audit_status=$?
set -e
cat "${audit_out}"
fail_deprecation_output "composer audit" "${audit_out}"
if [[ ${audit_status} -ne 0 ]]; then
    exit "${audit_status}"
fi
require_line "composer audit" "${audit_out}" '^No security vulnerability advisories found\.$'
rm -f "${audit_out}"

php scripts/check-cluster-pins.php > /tmp/check-cluster-pins.out
cat /tmp/check-cluster-pins.out
require_line "cluster pins" /tmp/check-cluster-pins.out '^check-cluster-pins: wrong-SHA demo rejected installed\.php reference$'
require_line "cluster pins" /tmp/check-cluster-pins.out '^check-cluster-pins: wrong-SHA demo rejected installed\.json source\.reference$'
require_line "cluster pins" /tmp/check-cluster-pins.out '^check-cluster-pins: vendor/composer/installed\.php sha256 unchanged \([a-f0-9]{64}\)$'
require_line "cluster pins" /tmp/check-cluster-pins.out '^check-cluster-pins: vendor/composer/installed\.json sha256 unchanged \([a-f0-9]{64}\)$'
require_line "cluster pins" /tmp/check-cluster-pins.out '^check-cluster-pins: org aliases 0; Packagist phpunit and phpstan are not index tips$'

php scripts/check-rename.php > /tmp/check-rename.out
cat /tmp/check-rename.out
require_line "rename check" /tmp/check-rename.out '^rename-check: no old repo-owned names remain$'

php scripts/lint-src.php > /tmp/lint-src.out
cat /tmp/lint-src.out
lint_line_file="$(mktemp)"
while IFS= read -r line; do
    printf '%s\n' "${line}" > "${lint_line_file}"
    require_line "lint-src" "${lint_line_file}" '^No syntax errors detected in .+$'
done < /tmp/lint-src.out
rm -f "${lint_line_file}"

if ! bash scripts/measure-upstream-baseline.sh > /tmp/measure-baseline.out 2>&1; then
    cat /tmp/measure-baseline.out
    echo 'measure-upstream-baseline failed' >&2
    exit 1
fi
cat /tmp/measure-baseline.out
require_line "upstream baseline" /tmp/measure-baseline.out '^upstream baseline recorded: OK \(619 tests, 970 assertions\)$'

mkdir -p /app/.system-test
php -r '
$src = "/app/phpunit.xml.dist";
$dst = "/app/.system-test/phpunit.xml";
$xml = file_get_contents($src);
$xml = preg_replace("#\\s*<logging>[\\s\\S]*?</logging>\\s*#", "", $xml);
$xml = preg_replace("#\\s*<filter>[\\s\\S]*?</filter>\\s*#", "", $xml);
$xml = str_replace("bootstrap=\"vendor/autoload.php\"", "bootstrap=\"/app/vendor/autoload.php\"", $xml);
$xml = str_replace("<directory>tests</directory>", "<directory>/app/tests</directory>", $xml);
file_put_contents($dst, $xml);
'

phpunit_out="$(mktemp)"
set +e
vendor/bin/phpunit --colors=never -c /app/.system-test/phpunit.xml > "${phpunit_out}" 2>&1
phpunit_status=$?
set -e
cat "${phpunit_out}"
fail_deprecation_output "phpunit" "${phpunit_out}"
if [[ ${phpunit_status} -ne 0 ]]; then
    exit "${phpunit_status}"
fi
ok_line="$(require_line "phpunit" "${phpunit_out}" '^OK \(619 tests, 970 assertions\)$')"
baseline_line="$(cat scripts/upstream-baseline.txt)"
baseline_file="$(mktemp)"
printf '%s\n' "${baseline_line}" > "${baseline_file}"
require_line "phpunit baseline file" "${baseline_file}" '^OK \(619 tests, 970 assertions\)$'
rm -f "${baseline_file}"

baseline_check_out="$(mktemp)"
php -r '
$finished = $argv[1];
$baseline = $argv[2];
$pat = "/^OK \\(619 tests, 970 assertions\\)$/"; 
if (!preg_match($pat, $finished) || !preg_match($pat, $baseline)) {
    fwrite(STDERR, "baseline compare: expected OK (619 tests, 970 assertions)\n");
    exit(1);
}
echo "baseline-check: upstream 619/970, finished 619/970\n";
' "${ok_line}" "${baseline_line}" > "${baseline_check_out}"
cat "${baseline_check_out}"
require_line "baseline check" "${baseline_check_out}" '^baseline-check: upstream 619/970, finished 619/970$'
rm -f "${baseline_check_out}"

phpstan_out="$(mktemp)"
phpstan_err="$(mktemp)"
set +e
vendor/bin/phpstan analyse -c tests/phpstan/phpstan.neon --error-format=json --no-progress --no-ansi > "${phpstan_out}" 2> "${phpstan_err}"
phpstan_status=$?
set -e
if [[ -s "${phpstan_err}" ]]; then
    cat "${phpstan_err}" >&2
fi
fail_deprecation_output "phpstan" "${phpstan_err}"
if [[ ${phpstan_status} -ne 0 ]]; then
    cat "${phpstan_out}" >&2
    exit "${phpstan_status}"
fi
require_line "phpstan" "${phpstan_out}" '^\{"totals":\{"errors":0,"file_errors":0\},"files":\[\],"errors":\[\]\}$'
rm -f "${phpstan_out}" "${phpstan_err}"

phpcsfixer_out="$(mktemp)"
phpcsfixer_err="$(mktemp)"
set +e
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --ansi > "${phpcsfixer_out}" 2> "${phpcsfixer_err}"
phpcsfixer_status=$?
set -e
if [[ -s "${phpcsfixer_err}" ]]; then
    cat "${phpcsfixer_err}" >&2
fi
fail_deprecation_output "php-cs-fixer" "${phpcsfixer_err}"
stripped="$(mktemp)"
strip_ansi < "${phpcsfixer_out}" > "${stripped}"
if [[ ${phpcsfixer_status} -ne 0 ]]; then
    cat "${stripped}" >&2
    rm -f "${stripped}" "${phpcsfixer_out}" "${phpcsfixer_err}"
    exit "${phpcsfixer_status}"
fi
if grep -E '^[[:space:]]+[0-9]+\) ' "${stripped}" >/dev/null; then
    cat "${stripped}" >&2
    rm -f "${stripped}" "${phpcsfixer_out}" "${phpcsfixer_err}"
    exit 1
fi
cat "${stripped}"
require_line "php-cs-fixer" "${stripped}" '^Checked all files in [0-9.]+ seconds, [0-9.]+ MB memory used$'
rm -f "${stripped}" "${phpcsfixer_out}" "${phpcsfixer_err}"

final_out="$(mktemp)"
echo 'system-test: all steps passed.' > "${final_out}"
require_line "system-test" "${final_out}" '^system-test: all steps passed\.$'
rm -f "${final_out}"
