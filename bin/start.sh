#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PORT="${APP_PORT:-1234}"
HOST="${APP_HOST:-127.0.0.1}"

echo "▶ Commerce Framework — starting local environment"
echo "  URL: http://${HOST}:${PORT}"
echo ""

if [ ! -f .env ]; then
    echo "→ Creating .env from .env.example"
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    echo "→ Installing PHP dependencies"
    composer install --no-interaction
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "→ Generating application key"
    php artisan key:generate --force
fi

if [ ! -d node_modules ]; then
    echo "→ Installing Node dependencies"
    npm install
fi

echo "→ Preparing database"
php artisan config:clear --quiet
php artisan migrate --force --quiet
php artisan db:seed --force --quiet

if [ ! -f public/build/manifest.json ]; then
    echo "→ Building frontend assets (first run)"
    npm run build
fi

echo ""
echo "✓ Ready — press Ctrl+C to stop"
echo "  Admin:  http://${HOST}:${PORT}/admin/login"
echo "  Email:  superadmin@example.com"
echo "  Pass:   password"
echo ""

exec npx concurrently \
    -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
    --names "server,queue,logs,vite" \
    --kill-others \
    "php artisan serve --host=${HOST} --port=${PORT}" \
    "php artisan queue:listen --tries=1 --timeout=0" \
    "php artisan pail --timeout=0" \
    "npm run dev"
