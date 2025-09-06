#!/bin/bash

# Rsync and deployment script
# This script handles the file synchronization and deployment process

# Set default values if environment variables are not provided
DEPLOYMENT_TYPE=${DEPLOYMENT_TYPE:-"UNKNOWN"}
SUBDOMAIN=${SUBDOMAIN:-"unknown"}
GITHUB_SHA=${GITHUB_SHA:-"unknown"}
GITHUB_REF=${GITHUB_REF:-"unknown"}
SSH_USER=${SSH_USER:-"unknown"}
SSH_SERVER=${SSH_SERVER:-"unknown"}
SSH_PORT=${SSH_PORT:-"22"}

echo "🚀 Starting deployment process..."
echo "📋 Deployment Info:"
echo "   Type: $DEPLOYMENT_TYPE"
echo "   Subdomain: $SUBDOMAIN"
echo "   Commit: $GITHUB_SHA"
echo "   Branch: $GITHUB_REF"
echo "   Server: $SSH_USER@$SSH_SERVER:$SSH_PORT"
echo ""

# Perform rsync to sync files
echo "📁 Syncing files to server..."
rsync -avz --delete -e "ssh -p $SSH_PORT" \
  --exclude=".git" \
  --exclude=".github" \
  ./ \
  $SSH_USER@$SSH_SERVER:~/domains/$SUBDOMAIN/public_html/

if [ $? -eq 0 ]; then
  echo "✅ File sync completed successfully"
else
  echo "❌ File sync failed"
  exit 1
fi

# Navigate to the deployment directory and run Laravel commands
echo "🔧 Running post-deployment tasks..."
cd ~/domains/$SUBDOMAIN/public_html

# Make deploy script executable and run it
chmod +x scripts/deploy.sh
./scripts/deploy.sh

if [ $? -eq 0 ]; then
  echo "🎉 Deployment completed successfully!"
  echo "✅ $DEPLOYMENT_TYPE environment ($SUBDOMAIN) is now updated"
else
  echo "❌ Post-deployment tasks failed"
  exit 1
fi
