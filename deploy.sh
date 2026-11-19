#!/usr/bin/env bash
# Legacy entry point. It used to pull and migrate without reloading PHP-FPM, and with OPcache
# timestamps off that left old PHP serving new JS bundles (SKI-MANAGER-1M). Delegate to the
# maintained script, which reloads php-fpm and fails the deploy if it can't.
set -euo pipefail
exec "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/scripts/deploy.sh" "${1:-production}"
