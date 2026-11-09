#!/usr/bin/env bash
set -e

APP_DIR="/srv/ski-manager/sites/skiv2/ci4"
if [ ! -d "$APP_DIR" ]; then
    APP_DIR="/opt/1panel/www/sites/skiv2/ci4"
fi
cd "$APP_DIR" || exit 1

echo "Pulling latest code..."
git pull origin main

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E '^ski-php$|^PHP$' | head -n 1 || true)

echo "Running migrations..."
if [ -n "$PHP_CONTAINER" ]; then
    docker exec "$PHP_CONTAINER" php /www/sites/skiv2/ci4/spark migrate
else
    php spark migrate
fi

echo "Clearing cache..."
if [ -n "$PHP_CONTAINER" ]; then
    docker exec "$PHP_CONTAINER" php /www/sites/skiv2/ci4/spark cache:clear
else
    php spark cache:clear
fi

echo "Recreating writable folders and permissions..."
mkdir -p writable/cache writable/session writable/logs writable/uploads writable/debugbar
chmod -R 777 writable/

echo "Deployment complete!"
