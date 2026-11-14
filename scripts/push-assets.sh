#!/usr/bin/env bash
# Builds public/build + public/css/style.css from this checkout and installs them on the server.
# Use it when you copy a branch to staging by hand: those files are not in git, so a checkout
# of the branch on the server has no built assets of its own.
#
# Usage: scripts/push-assets.sh staging|production
#   SKI_SSH ("root@72.62.170.146") and SKI_SSH_PORT (2222) override the connection.
set -euo pipefail

ENV="${1:?usage: scripts/push-assets.sh staging|production}"
case "${ENV}" in
    staging) SITE="/srv/ski-manager/sites/skiv2-staging/ci4" ;;
    production) SITE="/srv/ski-manager/sites/skiv2/ci4" ;;
    *) echo "ERROR: unknown environment '${ENV}'" >&2; exit 1 ;;
esac
SSH="${SKI_SSH:-root@72.62.170.146}"
PORT="${SKI_SSH_PORT:-2222}"

if [ "${ENV}" = "production" ]; then
    read -r -p "Install this checkout's assets on PRODUCTION? Type yes: " ok
    [ "${ok}" = "yes" ] || { echo "Cancelled."; exit 1; }
fi

cd "$(dirname "$0")/.."
npm run build
BUNDLE="$(mktemp -t ski-assets).tgz"
trap 'rm -f "${BUNDLE}"' EXIT
# COPYFILE_DISABLE keeps macOS ._* metadata files out of the bundle
COPYFILE_DISABLE=1 tar -czf "${BUNDLE}" public/build public/css/style.css

REMOTE="/tmp/ski-assets-manual-$$.tgz"
scp -P "${PORT}" -q "${BUNDLE}" "${SSH}:${REMOTE}"
ssh -p "${PORT}" "${SSH}" "bash -s -- '${REMOTE}' '${SITE}'" < scripts/install-assets.sh
