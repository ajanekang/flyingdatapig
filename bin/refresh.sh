#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"

if ! command -v docker >/dev/null 2>&1; then
    echo "docker is required but not installed." >&2
    exit 1
fi

exec docker run --rm \
    -v "$PWD:/app" -w /app \
    "$PHP_IMAGE" \
    php bin/refresh_data.php
