#!/usr/bin/env bash
#
# One-shot setup for this project. Safe to re-run — every step is idempotent
# (skips what's already done). Requires Docker + Docker Compose; nothing else
# on the host (no local PHP/Composer needed).
#
# Usage: ./setup.sh

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

log() { echo -e "\n\033[1;34m==> $1\033[0m"; }
warn() { echo -e "\033[1;33m! $1\033[0m"; }

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is required. Install it first: https://docs.docker.com/get-docker/"
    exit 1
fi

# 1. .env
if [ ! -f .env ]; then
    log "Membuat .env dari .env.example"
    cp .env.example .env
else
    log ".env sudah ada, dilewati"
fi

# 2. Dependencies (bootstrap via Docker's official Composer image if there's
#    no local PHP/Composer — this is the only step that doesn't need Sail yet)
if [ ! -d vendor ]; then
    log "Install dependencies PHP (composer install)"
    if command -v composer >/dev/null 2>&1; then
        composer install
    else
        docker run --rm -u "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 install --ignore-platform-reqs
    fi
else
    log "vendor/ sudah ada, dilewati"
fi

SAIL="vendor/bin/sail"

# 3. APP_KEY
if ! grep -q "^APP_KEY=base64:" .env; then
    log "Generate APP_KEY"
    $SAIL artisan key:generate --ansi 2>/dev/null || {
        # Containers not up yet — bring them up first, then retry.
        $SAIL up -d
        $SAIL artisan key:generate --ansi
    }
else
    log "APP_KEY sudah ada, dilewati"
fi

# 4. Bring up the stack (app + queue worker + postgres + redis)
log "Menjalankan containers (sail up -d)"
$SAIL up -d

# 5. Wait for Postgres to be ready before migrating
log "Menunggu database siap"
until $SAIL artisan db:show >/dev/null 2>&1; do
    sleep 1
done

# 6. FrankenPHP binary (gitignored, ~166MB — downloaded once, used by Octane)
if [ ! -f frankenphp ]; then
    log "Download FrankenPHP binary (Octane server)"
    $SAIL artisan octane:install --server=frankenphp --no-interaction
    docker restart "$(basename "$PWD")-laravel.test-1" >/dev/null 2>&1 || true
else
    log "FrankenPHP binary sudah ada, dilewati"
fi

# 7. Database schema
log "Menjalankan migration"
$SAIL artisan migrate --force

# 8. Front-end assets (only needed for the default welcome page)
if command -v npm >/dev/null 2>&1 && [ ! -d public/build ]; then
    log "Build front-end assets (npm)"
    npm install && npm run build
fi

log "Setup selesai."

missing=()
grep -q "^SISTER_API_USERNAME=$" .env && missing+=("SISTER_API_USERNAME / SISTER_API_PASSWORD / SISTER_API_ID_PENGGUNA (kredensial sandbox SISTER)")
grep -q "^GEMINI_API_KEY=$" .env && missing+=("GEMINI_API_KEY (aistudio.google.com/apikey)")
grep -q "^OPENROUTER_API_KEY=$" .env && missing+=("OPENROUTER_API_KEY (openrouter.ai — opsional, fallback saat Gemini overload)")

if [ ${#missing[@]} -gt 0 ]; then
    warn "Isi dulu di .env sebelum fitur-fitur berikut bisa jalan:"
    for m in "${missing[@]}"; do echo "  - $m"; done
    echo "Setelah diisi, restart container: docker restart \$(basename \$PWD)-laravel.test-1"
fi

port=$(grep ^APP_PORT= .env | cut -d= -f2)
echo ""
echo "Buka: http://localhost:${port}/crawl-runs"
echo "      http://localhost:${port}/ai-search"
