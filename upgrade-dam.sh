#!/bin/bash
# upgrade-dam.sh — update a manually-installed Unopim DAM package, safely.
# Run from the Unopim project root:  bash packages/Webkul/DAM/upgrade-dam.sh
#
# Fetches the latest GitHub release, swaps packages/Webkul/DAM (code only —
# your DB and asset files are untouched), then runs `php artisan dam:update`,
# which backs up DAM tables + asset files, migrates, publishes, and verifies
# that no data was lost.
set -euo pipefail

GITHUB_REPO="unopim/unopim-digital-asset-management"
PKG_DIR="packages/Webkul/DAM"
TMP_DIR="./.dam_upgrade_tmp"

command -v php >/dev/null   || { echo "❌ php not found (run from the project root)"; exit 1; }
command -v curl >/dev/null  || { echo "❌ curl not found"; exit 1; }
command -v unzip >/dev/null || { echo "❌ unzip not found"; exit 1; }

INSTALLED=$(php artisan dam:version | tr -d '[:space:]')
echo "📌 Installed DAM version: $INSTALLED"

RELEASE_JSON=$(curl -fsSL -H "Accept: application/vnd.github+json" \
  "https://api.github.com/repos/$GITHUB_REPO/releases/latest")
LATEST_TAG=$(echo "$RELEASE_JSON" | grep -oP '"tag_name":\s*"\K[^"]+')
LATEST="${LATEST_TAG#v}"
[ -n "$LATEST" ] || { echo "❌ Could not read latest release tag"; exit 1; }
echo "✅ Latest DAM version: $LATEST"

if [ "$INSTALLED" = "$LATEST" ]; then
  echo "✅ Already up to date."
  exit 0
fi

ZIP_URL="https://github.com/$GITHUB_REPO/archive/refs/tags/$LATEST_TAG.zip"
echo "⬇️  Downloading $LATEST_TAG ..."
rm -rf "$TMP_DIR"; mkdir -p "$TMP_DIR"
curl -fSL -o "$TMP_DIR/dam.zip" "$ZIP_URL"
unzip -tq "$TMP_DIR/dam.zip" >/dev/null || { echo "❌ Invalid zip"; rm -rf "$TMP_DIR"; exit 1; }
unzip -q "$TMP_DIR/dam.zip" -d "$TMP_DIR"

EXTRACTED=$(find "$TMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n1)
echo "🔄 Swapping $PKG_DIR (code only; your data is untouched) ..."
rm -rf "$PKG_DIR".old 2>/dev/null || true
mv "$PKG_DIR" "$PKG_DIR".old
mkdir -p "$PKG_DIR"
cp -a "$EXTRACTED"/. "$PKG_DIR"/
rm -rf "$TMP_DIR" "$PKG_DIR".old

echo "🛠️  Finalizing via php artisan dam:update ..."
exec php artisan dam:update
