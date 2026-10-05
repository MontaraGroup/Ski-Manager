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
    HOST_PATH="/opt/1panel/www/sites/skiv2-staging/ci4"
    CONTAINER_PATH="/www/sites/skiv2-staging/ci4"
    BRANCH="staging"
    DOMAIN="https://staging.ski-manager.net"
elif [ "${ENV}" = "production" ]; then
    HOST_PATH="/opt/1panel/www/sites/skiv2/ci4"
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

# 2. Locate 1Panel Docker Containers
PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E '^PHP$|^1panel-.*php.*' | head -n 1 || true)
if [ -z "${PHP_CONTAINER}" ]; then
    PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep -iE 'php' | head -n 1 || echo "PHP")
fi

MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E 'mysql' | head -n 1 || echo "1Panel-mysql-Uibp")
OPENRESTY_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E 'openresty|nginx' | head -n 1 || echo "1Panel-openresty-Xqgf")

echo "Detected Containers:"
echo "  - PHP:       ${PHP_CONTAINER}"
echo "  - MySQL:     ${MYSQL_CONTAINER}"
echo "  - OpenResty: ${OPENRESTY_CONTAINER}"

# 3. Pre-deploy Database Backup (Production only)
if [ "${ENV}" = "production" ]; then
    BACKUP_DIR="/opt/1panel/backup/pre_deploy"
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

# 5. Run Database Migrations Inside PHP Container
echo "Executing database migrations..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" php spark migrate || {
        echo "WARNING: Migrations encountered an issue or no pending migrations."
    }
else
    echo "WARNING: PHP container ${PHP_CONTAINER} not running, attempting host spark..."
    php spark migrate || true
fi

# 6. Clear Application, Route & View Caches
echo "Clearing application cache..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    docker exec -w "${CONTAINER_PATH}" "${PHP_CONTAINER}" php spark cache:clear || true
else
    php spark cache:clear || true
fi

# 7. Reload Bytecode Cache (PHP-FPM) and Reverse Proxy (OpenResty)
echo "Reloading runtime workers..."
if docker ps --format '{{.Names}}' | grep -q "${PHP_CONTAINER}"; then
    docker exec "${PHP_CONTAINER}" kill -USR2 1 2>/dev/null || true
fi

if docker ps --format '{{.Names}}' | grep -q "${OPENRESTY_CONTAINER}"; then
    docker exec "${OPENRESTY_CONTAINER}" openresty -s reload 2>/dev/null || true
fi

# 8. Post-Deploy Smoke Test & Health Check
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
