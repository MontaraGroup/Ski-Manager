#!/usr/bin/env bash
# Puts a CI-built asset bundle (public/build + public/css/style.css) into a site checkout.
# Those files are not in git, so deploys upload them here instead of building on this 1-CPU server.
#
# Usage: scripts/install-assets.sh <bundle.tgz> <site root, e.g. /srv/ski-manager/sites/skiv2/ci4>
set -euo pipefail

BUNDLE="${1:?bundle .tgz path required}"
SITE="${2:?site root required}"
[ -f "${BUNDLE}" ] || { echo "ERROR: ${BUNDLE} not found" >&2; exit 1; }
[ -d "${SITE}/public" ] || { echo "ERROR: ${SITE}/public not found" >&2; exit 1; }

# Unpack next to the site so the final moves stay on one filesystem
STAGE="$(mktemp -d "${SITE}/.assets.XXXXXX")"
trap 'rm -rf "${STAGE}"' EXIT
tar -xzf "${BUNDLE}" -C "${STAGE}" --no-same-owner

if [ ! -f "${STAGE}/public/build/.vite/manifest.json" ] || [ ! -f "${STAGE}/public/css/style.css" ]; then
    echo "ERROR: bundle is missing the Vite manifest or style.css" >&2
    exit 1
fi

mkdir -p "${SITE}/public/build"
# Copy hashed chunks first and the manifest last, so a page never points at a chunk that isn't there yet.
# No --delete: a player with a page open from before the deploy still loads its old chunks.
rsync -a --exclude '.vite/' "${STAGE}/public/build/" "${SITE}/public/build/"
rsync -a "${STAGE}/public/build/.vite/" "${SITE}/public/build/.vite/"
cp "${STAGE}/public/css/style.css" "${SITE}/public/css/style.css.new"
mv -f "${SITE}/public/css/style.css.new" "${SITE}/public/css/style.css"

# Old chunks are kept a week, then removed
find "${SITE}/public/build/assets" -type f -mtime +7 -delete

chmod -R u=rwX,go=rX "${SITE}/public/build" "${SITE}/public/css/style.css"
rm -f "${BUNDLE}"
echo "Assets installed in ${SITE} ($(find "${SITE}/public/build/assets" -type f | wc -l) files in build/assets)"
