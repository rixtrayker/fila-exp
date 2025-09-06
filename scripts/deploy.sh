#!/bin/bash

# Laravel deployment script
# This script handles the post-deployment tasks for Laravel applications

# Set default values if environment variables are not provided
DEPLOYMENT_TYPE=${DEPLOYMENT_TYPE:-"UNKNOWN"}
SUBDOMAIN=${SUBDOMAIN:-"unknown"}
GITHUB_SHA=${GITHUB_SHA:-"unknown"}
GITHUB_REF=${GITHUB_REF:-"unknown"}

echo "🚀 Starting Laravel deployment tasks..."
echo "📋 Deployment Info:"
echo "   Type: $DEPLOYMENT_TYPE"
echo "   Subdomain: $SUBDOMAIN"
echo "   Commit: $GITHUB_SHA"
echo "   Branch: $GITHUB_REF"
echo ""

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
echo "🎉 Deployed to $DEPLOYMENT_TYPE environment ($SUBDOMAIN)"
