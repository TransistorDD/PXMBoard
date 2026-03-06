#!/bin/bash
# Local release build script
# Creates a deploy/ directory ready for FTP upload.

set -e

DEPLOY_DIR="./deploy"

echo "==> Starting build..."
npm run build
composer install --no-dev --optimize-autoloader --classmap-authoritative --quiet

echo "==> Preparing deploy directory..."
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

rsync -a \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.gitignore' \
  --exclude '.vscode/' \
  --exclude 'node_modules/' \
  --exclude 'build/' \
  --exclude '/css/' \
  --exclude 'tests/' \
  --exclude 'stories/' \
  --exclude 'docs/' \
  --exclude 'coverage/' \
  --exclude 'reports/' \
  --exclude 'deploy/' \
  --exclude '*.sh' \
  --exclude 'phpunit.xml' \
  --exclude 'phpstan.neon' \
  --exclude 'phpstan_bericht.txt' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude 'vite.config.js' \
  --exclude 'composer.json' \
  --exclude 'composer.lock' \
  --exclude 'Agents.md' \
  --exclude 'TESTING.md' \
  --exclude 'install.txt' \
  --exclude '.phpunit.cache/' \
  --exclude '.phpunit.result.cache' \
  --exclude 'config/pxmboard-config.php' \
  --exclude 'skins/pxm/cache/*' \
  . "$DEPLOY_DIR/"

echo ""
echo "==> Done. Upload the following directories via FTP:"
echo "    deploy/public/    -> public/ (webroot)"
echo "    deploy/config/    -> config/"
echo "    deploy/lang/      -> lang/"
echo "    deploy/skins/     -> skins/"
echo "    deploy/src/       -> src/"
echo "    deploy/vendor/    -> vendor/"
echo "    deploy/install/   -> install/ (optional)"
echo ""
echo "    DO NOT overwrite: config/pxmboard-config.php"
