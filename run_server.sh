#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"
PORT=38005
CONTAINER_NAME="flyingdatapig-server"

if ! command -v docker >/dev/null 2>&1; then
    echo "docker is required but not installed." >&2
    exit 1
fi

# Free up the port: stop our own container if running, any other docker
# container publishing the port, and any host process listening on it.
free_port() {
    local port=$1

    docker rm -f "$CONTAINER_NAME" >/dev/null 2>&1 || true

    local cids
    cids=$(docker ps --filter "publish=${port}" -q)
    if [ -n "$cids" ]; then
        echo "Stopping docker containers on port ${port}: ${cids}"
        docker stop $cids >/dev/null
    fi

    if command -v lsof >/dev/null 2>&1; then
        local pids
        pids=$(lsof -nP -iTCP:"${port}" -sTCP:LISTEN -t 2>/dev/null || true)
        if [ -n "$pids" ]; then
            echo "Killing host processes on port ${port}: ${pids}"
            kill $pids 2>/dev/null || true
            sleep 0.3
            pids=$(lsof -nP -iTCP:"${port}" -sTCP:LISTEN -t 2>/dev/null || true)
            [ -n "$pids" ] && kill -9 $pids 2>/dev/null || true
        fi
    fi
}

free_port "$PORT"

docker pull "$PHP_IMAGE"

exec docker run --rm -it \
    --name "$CONTAINER_NAME" \
    -p "127.0.0.1:${PORT}:${PORT}" \
    -v "$PWD:/app" \
    -w /app \
    "$PHP_IMAGE" \
    php -S "0.0.0.0:${PORT}"
