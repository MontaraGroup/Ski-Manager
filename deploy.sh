#!/usr/bin/env bash
set -e

APP_DIR="/opt/1panel/www/sites/skiv2/ci4"
cd "$APP_DIR" || exit 1

echo "📥 Pulling latest code..."
git pull origin main

echo "🗄️ Running migrations..."
php spark migrate

echo "🧹 Clearing cache..."
php spark cache:clear

echo "🔒 Recreating writable folders & permissions..."
mkdir -p writable/cache writable/session writable/logs writable/uploads writable/debugbar
chmod -R 777 writable/

echo "✅ Deployment complete!"
