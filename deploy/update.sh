#!/bin/bash
set -e

# ===========================================
# CRM - Auto-deploy script (triggered by webhook)
# ===========================================

APP_DIR="/var/www/crm"
LOG_FILE="/var/log/crm-deploy.log"

echo "$(date '+%Y-%m-%d %H:%M:%S') - Deploy triggered" >> "$LOG_FILE"

cd "$APP_DIR"

# Pull latest changes
git pull origin main >> "$LOG_FILE" 2>&1

# Install dependencies (skip dev)
composer install --no-dev --optimize-autoloader --no-interaction >> "$LOG_FILE" 2>&1

# Run migrations
php artisan migrate --force >> "$LOG_FILE" 2>&1

# Clear caches
php artisan optimize:clear >> "$LOG_FILE" 2>&1

# Rebuild assets
npm install --production=false >> "$LOG_FILE" 2>&1
npm run build >> "$LOG_FILE" 2>&1

# Fix permissions
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "$(date '+%Y-%m-%d %H:%M:%S') - Deploy complete ✅" >> "$LOG_FILE"
