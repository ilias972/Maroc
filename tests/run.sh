#!/usr/bin/env bash
set -euo pipefail

echo ">> Lint PHP sources"
PHP_FILES=$(find public includes data tests -name '*.php' -not -path '*/vendor/*')
for file in ${PHP_FILES}; do
  php -l "${file}" > /dev/null
done
echo "✅ Lint OK"

echo ">> Smoke tests"
php tests/smoke.php
echo "✅ Smoke OK"
