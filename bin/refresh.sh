#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"

if command -v docker >/dev/null 2>&1; then
    exec docker run --rm \
        -v "$PWD:/app" -w /app \
        "$PHP_IMAGE" \
        php bin/refresh_data.php
fi

if command -v php >/dev/null 2>&1; then
    exec php bin/refresh_data.php
fi

echo "Neither docker nor php is available on PATH." >&2
exit 1
