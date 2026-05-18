#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"
PORT=38005
CONTAINER_NAME="flyingdatapig-server"

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

exec docker run --rm -it \
    --name "$CONTAINER_NAME" \
    -p "127.0.0.1:${PORT}:${PORT}" \
    -v "$PWD:/app" \
    -w /app \
    "$PHP_IMAGE" \
    php -S "0.0.0.0:${PORT}"
