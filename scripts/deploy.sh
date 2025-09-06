#!/bin/bash

# Laravel deployment script
# This script handles the post-deployment tasks for Laravel applications

echo "🚀 Starting Laravel deployment tasks..."

# Run database migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "⚙️ Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache

# Cache routes and views
echo "🛣️ Caching routes..."
php artisan route:cache

echo "👁️ Caching views..."
php artisan view:cache

echo "✅ Laravel deployment tasks completed successfully!"
