#!/usr/bin/env bash
# ==============================================================================
# Unified Deployment Script for Ski Manager (Production & Staging)
# ==============================================================================
# Usage:
#   ./scripts/deploy.sh [production|staging]
# ==============================================================================

set -euo pipefail

ENV="${1:-production}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

echo "=========================================================="
echo " Starting deployment for environment: [${ENV}]"
echo " Timestamp: ${TIMESTAMP}"
echo "=========================================================="

# 1. Environment-specific settings
if [ "${ENV}" = "staging" ]; then
    HOST_PATH="/srv/ski-manager/sites/skiv2-staging/ci4"
    CONTAINER_PATH="/www/sites/skiv2-staging/ci4"
    BRANCH="staging"
    DOMAIN="https://staging.ski-manager.net"
elif [ "${ENV}" = "production" ]; then
    HOST_PATH="/srv/ski-manager/sites/skiv2/ci4"
    CONTAINER_PATH="/www/sites/skiv2/ci4"
    BRANCH="main"
    DOMAIN="https://ski-manager.net"
else
    echo "ERROR: Unknown environment '${ENV}'. Allowed: production, staging" >&2
    exit 1
fi

# Ensure we are in the target directory if running on the server
if [ -d "${HOST_PATH}" ]; then
    cd "${HOST_PATH}"
fi

echo "Working directory: $(pwd)"

# 2. Locate Docker Containers
PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E '^ski-php$|^PHP$|^1panel-.*php.*' | head -n 1 || true)
if [ -z "${PHP_CONTAINER}" ]; then
    PHP_CONTAINER="ski-php"
fi

MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E '^ski-mysql$|mysql' | head -n 1 || echo "ski-mysql")
OPENRESTY_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E '^ski-proxy$|openresty|nginx' | head -n 1 || echo "ski-proxy")

echo "Detected Containers:"
echo "  - PHP:       ${PHP_CONTAINER}"
echo "  - MySQL:     ${MYSQL_CONTAINER}"
echo "  - OpenResty: ${OPENRESTY_CONTAINER}"

# 3. Pre-deploy Database Backup (Production only)
if [ "${ENV}" = "production" ]; then
    BACKUP_DIR="/root/db_backups/pre_deploy"
    mkdir -p "${BACKUP_DIR}" 2>/dev/null || BACKUP_DIR="/tmp"
    BACKUP_FILE="${BACKUP_DIR}/backup_pre_deploy_${TIMESTAMP}.sql.gz"

    echo "Creating pre-deployment database backup..."
    if docker ps --format '{{.Names}}' | grep -q "${MYSQL_CONTAINER}"; then
        docker exec "${MYSQL_CONTAINER}" mysqldump -u root --all-databases 2>/dev/null | gzip > "${BACKUP_FILE}" || \
        docker exec "${MYSQL_CONTAINER}" mysqldump -u root -p"$(docker exec "${MYSQL_CONTAINER}" printenv MYSQL_ROOT_PASSWORD 2>/dev/null || true)" --all-databases 2>/dev/null | gzip > "${BACKUP_FILE}" || true

        if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
            echo "Backup saved: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"
            # Retain only last 5 pre-deploy backups
            ls -t "${BACKUP_DIR}"/backup_pre_deploy_*.sql.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
        else
            echo "Notice: Snapshot skipped or empty, proceeding with deploy."
        fi
    fi
fi

# 4. Clean Git Synchronization
echo "Syncing code with origin/${BRANCH}..."
git fetch origin "${BRANCH}"
git reset --hard "origin/${BRANCH}"

# 5. PHP Dependencies (Composer)
if [ -f "composer.json" ]; then
    echo "Updating composer dependencies..."
    if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}" && docker exec "${PHP_CONTAINER}" which composer &>/dev/null; then
        echo "Running 'composer install' inside ${PHP_CONTAINER}..."
        docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" composer install --no-dev --optimize-autoloader --no-interaction || true
    elif command -v composer &>/dev/null; then
        echo "Running 'composer install' on host..."
        composer install --no-dev --optimize-autoloader --no-interaction || true
    fi
fi

# 6. Project NPM Dependencies & Asset Compilation
if [ -f "package.json" ]; then
    echo "Updating project npm dependencies in $(pwd)..."
    if command -v npm &>/dev/null; then
        echo "Running 'npm install' on host in $(pwd)..."
        npm install --no-audit --no-fund || npm update || true
        echo "Active HeroUI package: $(npm list --depth=0 2>/dev/null | grep heroui || true)"
        if grep -q '"build":' package.json; then
            echo "Rebuilding project assets ('npm run build')..."
            npm run build || true
        fi
    elif docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}" && docker exec "${PHP_CONTAINER}" which npm &>/dev/null; then
        echo "Running 'npm install' inside container ${PHP_CONTAINER}..."
        docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" npm install --no-audit --no-fund || docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" npm update || true
        if grep -q '"build":' package.json; then
            echo "Building frontend assets inside ${PHP_CONTAINER}..."
            docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" npm run build || true
        fi
    fi
fi

# 6. Run Database Migrations Inside PHP Container
echo "Executing database migrations..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" php spark migrate || {
        echo "WARNING: Migrations encountered an issue or no pending migrations."
    }
else
    echo "WARNING: PHP container ${PHP_CONTAINER} not running, attempting host spark..."
    php spark migrate || true
fi

# 7. Clear Application, Route & View Caches
echo "Clearing application cache..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" php spark cache:clear || true
else
    php spark cache:clear || true
fi

# 8. Reload Bytecode Cache (PHP-FPM) and Reverse Proxy (OpenResty)
echo "Reloading runtime workers..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    # PID 1 in the container is supervisord, which treats USR2 as "reopen logs"; signal php-fpm itself.
    # OPcache does not check file timestamps, so this graceful reload is what makes new code live.
    # Without it, the new JS bundles go live against old PHP (e.g. SKI-MANAGER-1M), so a failed reload must fail the deploy.
    if docker exec "${PHP_CONTAINER}" supervisorctl signal USR2 "php-fpm:*" >/dev/null 2>&1; then
        echo "PHP-FPM reloaded via supervisorctl."
    elif docker exec "${PHP_CONTAINER}" sh -c 'kill -USR2 "$(cat /var/run/php-fpm.pid 2>/dev/null || pgrep -o php-fpm)"' 2>/dev/null; then
        echo "PHP-FPM reloaded via direct USR2 to the master process."
    else
        echo "ERROR: Could not reload PHP-FPM in ${PHP_CONTAINER}; new PHP code is NOT live (OPcache ignores file timestamps)." >&2
        echo "       Restart the container (docker restart ${PHP_CONTAINER}) and re-run this deploy." >&2
        exit 1
    fi

    # Confirm php-fpm came back up after the graceful reload before smoke-testing.
    sleep 1
    if docker exec "${PHP_CONTAINER}" sh -c 'command -v supervisorctl >/dev/null 2>&1' \
        && ! docker exec "${PHP_CONTAINER}" supervisorctl status "php-fpm:*" 2>/dev/null | grep -q RUNNING; then
        echo "ERROR: php-fpm is not RUNNING in ${PHP_CONTAINER} after reload." >&2
        exit 1
    fi
fi

if docker ps --format '{{.Names}}' | grep -q "${OPENRESTY_CONTAINER}"; then
    docker exec "${OPENRESTY_CONTAINER}" openresty -s reload 2>/dev/null || true
fi

# 9. Post-Deploy Smoke Test & Health Check
echo "Running post-deploy health check on ${DOMAIN}..."
sleep 2

HEALTH_CHECK_PASSED=false
for attempt in 1 2 3; do
    HTTP_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" "${DOMAIN}/health" || echo "000")
    if [ "${HTTP_CODE}" = "200" ]; then
        HEALTH_CHECK_PASSED=true
        echo "Health check PASSED: HTTP 200 OK (${DOMAIN}/health)"
        break
    else
        echo "Attempt ${attempt}: ${DOMAIN}/health returned HTTP ${HTTP_CODE}. Waiting 2s..."
        sleep 2
    fi
done

if [ "${HEALTH_CHECK_PASSED}" = "false" ]; then
    echo "WARNING: Health check did not return 200. Inspect server logs at ${HOST_PATH}/writable/logs/"
    # If health check fails on production, exit with error so CI/CD pipeline alerts
    exit 1
fi

echo "=========================================================="
echo " Deployment to [${ENV}] completed successfully!"
echo " Commit: $(git rev-parse --short HEAD)"
echo "=========================================================="
