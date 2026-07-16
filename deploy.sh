#!/usr/bin/env bash
# Manual mirror of .github/workflows/deploy.yml — run this on the server
# from the app directory if you need to redeploy without pushing to GitHub
# (e.g. after building assets locally and copying public/build over).
set -euo pipefail

cd "$(dirname "$0")"

echo "==> Pulling latest code"
git fetch origin main
git reset --hard origin/main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Caching configuration"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy complete. Note: this script does not build frontend assets."
echo "==> If public/build is stale, build it separately (npm ci && npm run build) and copy it over."
