#!/bin/bash

# Laravel deployment script
# This script handles the post-deployment tasks for Laravel applications

set -euo pipefail

PHP_BIN="${PHP_BIN:-/opt/alt/php82/usr/bin/php}"

if [ ! -x "$PHP_BIN" ]; then
    echo "❌ Required PHP 8.2 CLI binary not found: $PHP_BIN"
    exit 1
fi

echo "🚀 Starting Laravel deployment tasks..."

# Run database migrations
echo "📊 Running database migrations..."
if ! "$PHP_BIN" artisan migrate --force; then
    echo "⚠️ Migration command failed immediately after sync; retrying once..."
    sleep 3
    "$PHP_BIN" artisan migrate --force
fi

# Clear and cache configuration
echo "⚙️ Clearing and caching configuration..."
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan config:cache

# Closure-based operations routes cannot be cached.
echo "🛣️ Clearing route cache..."
"$PHP_BIN" artisan route:clear

echo "👁️ Caching views..."
"$PHP_BIN" artisan view:cache

echo "✅ Laravel deployment tasks completed successfully!"
