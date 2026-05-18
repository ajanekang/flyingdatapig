#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"
PORT=38005
CONTAINER_NAME="flyingdatapig-debug"
URL="http://127.0.0.1:${PORT}"

# Install Docker if it isn't on PATH. macOS uses Homebrew + Docker Desktop;
# Linux uses the official get.docker.com script (needs sudo). On macOS we
# also launch Docker Desktop and wait for the daemon to be reachable so the
# subsequent docker pull/run won't fail with "cannot connect".
ensure_docker() {
    if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        return 0
    fi

    local os
    os=$(uname -s)

    if ! command -v docker >/dev/null 2>&1; then
        echo "Docker not found. Installing ..." >&2
        case "$os" in
            Darwin)
                if ! command -v brew >/dev/null 2>&1; then
                    echo "Homebrew is required to auto-install Docker on macOS." >&2
                    echo "Install Homebrew from https://brew.sh and re-run, or install Docker Desktop manually." >&2
                    exit 1
                fi
                brew install --cask docker
                ;;
            Linux)
                if ! command -v curl >/dev/null 2>&1; then
                    echo "curl is required to auto-install Docker on Linux." >&2
                    exit 1
                fi
                curl -fsSL https://get.docker.com | sh
                ;;
            *)
                echo "Unsupported OS '$os'. Install Docker manually." >&2
                exit 1
                ;;
        esac
    fi

    # macOS: launch Docker Desktop and wait for the daemon.
    if [ "$os" = "Darwin" ] && ! docker info >/dev/null 2>&1; then
        echo "Starting Docker Desktop ..." >&2
        open -a Docker >/dev/null 2>&1 || true
        local i
        for i in $(seq 1 60); do
            if docker info >/dev/null 2>&1; then
                echo "Docker daemon is ready." >&2
                return 0
            fi
            sleep 2
        done
        echo "Docker daemon did not come up. Start Docker Desktop manually and retry." >&2
        exit 1
    fi

    if ! docker info >/dev/null 2>&1; then
        echo "Docker is installed but the daemon is unreachable. Start it and retry." >&2
        exit 1
    fi
}

ensure_docker

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

# Debug mode: surface all errors, enable assertions.
# The stock php:8.3-cli image does not bundle Xdebug. If you need
# step-debugging, build a custom image that installs it via:
#   pecl install xdebug && docker-php-ext-enable xdebug
docker run --rm -d \
    --name "$CONTAINER_NAME" \
    -p "127.0.0.1:${PORT}:${PORT}" \
    -v "$PWD:/app" \
    -w /app \
    "$PHP_IMAGE" \
    php \
        -d display_errors=1 \
        -d display_startup_errors=1 \
        -d error_reporting=E_ALL \
        -d log_errors=1 \
        -d assert.active=1 \
        -d zend.assertions=1 \
        -S "0.0.0.0:${PORT}" >/dev/null

cleanup() {
    docker stop "$CONTAINER_NAME" >/dev/null 2>&1 || true
}
trap cleanup INT TERM EXIT

# Wait for the server to start responding before opening the browser.
echo "Waiting for ${URL} to come up ..."
for _ in $(seq 1 40); do
    if curl -fsS -o /dev/null "${URL}/" 2>/dev/null; then
        echo "Server is ready."
        break
    fi
    sleep 0.25
done

# Open the URL in the default browser (macOS: open, Linux: xdg-open).
if command -v open >/dev/null 2>&1; then
    open "${URL}" || true
elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "${URL}" >/dev/null 2>&1 &
fi

# Stream container logs so this script blocks until Ctrl+C; the EXIT trap
# stops the container on signal.
docker logs -f "$CONTAINER_NAME"
