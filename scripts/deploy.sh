#!/bin/bash

# Laravel deployment script
# This script handles the post-deployment tasks for Laravel applications

set -euo pipefail

PHP_BIN="${PHP_BIN:-/opt/alt/php82/usr/bin/php}"

if [ ! -x "$PHP_BIN" ]; then
    echo "❌ Required PHP 8.2 CLI binary not found: $PHP_BIN"
    exit 1
fi

artisan() {
    "$PHP_BIN" -d memory_limit=512M -d display_errors=1 artisan "$@"
}

echo "🚀 Starting Laravel deployment tasks..."

# Run database migrations
echo "📊 Running database migrations..."
if ! artisan migrate --force; then
    echo "⚠️ Migration command failed immediately after sync; retrying once..."
    sleep 3
    artisan migrate --force
fi

# Clear and cache configuration
echo "⚙️ Clearing and caching configuration..."
artisan config:clear
artisan config:cache

# Closure-based operations routes cannot be cached.
echo "🛣️ Clearing route cache..."
artisan route:clear

echo "👁️ Caching views..."
artisan view:cache

echo "✅ Laravel deployment tasks completed successfully!"
