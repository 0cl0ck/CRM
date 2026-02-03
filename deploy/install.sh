#!/bin/bash
set -e

# ===========================================
# Laravel CRM - Deployment Script for Hetzner VPS
# Ubuntu 22.04+ | PHP 8.2 | Caddy | SQLite
# ===========================================

echo "🚀 Starting CRM deployment..."

# Colors for output
GREEN='\033[0;32m'
NC='\033[0m'

# --- 1. System Update ---
echo -e "${GREEN}[1/6] Updating system...${NC}"
apt update && apt upgrade -y

# --- 2. Install PHP 8.2 + Extensions ---
echo -e "${GREEN}[2/6] Installing PHP 8.2...${NC}"
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.2-fpm php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath unzip git

# --- 3. Install Composer ---
echo -e "${GREEN}[3/6] Installing Composer...${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- 4. Install Caddy ---
echo -e "${GREEN}[4/6] Installing Caddy...${NC}"
apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
apt update
apt install -y caddy

# --- 5. Deploy Application ---
echo -e "${GREEN}[5/6] Deploying application...${NC}"
mkdir -p /var/www
cd /var/www

# Clone or pull
if [ -d "crm" ]; then
    cd crm && git pull
else
    git clone https://github.com/0cl0ck/CRM.git crm
    cd crm
fi

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup environment
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Create SQLite database if not exists
touch database/database.sqlite

# Run migrations
php artisan migrate --force

# Build assets
npm install && npm run build

# Set permissions
chown -R www-data:www-data /var/www/crm
chmod -R 775 storage bootstrap/cache

# --- 6. Configure Caddy ---
echo -e "${GREEN}[6/6] Configuring Caddy...${NC}"
cp /var/www/crm/deploy/Caddyfile /etc/caddy/Caddyfile
systemctl restart caddy
systemctl enable caddy

# --- Done ---
echo ""
echo -e "${GREEN}✅ Deployment complete!${NC}"
echo ""
echo "Your CRM is now available at: http://$(curl -s ifconfig.me)"
echo ""
echo "Next steps:"
echo "  1. Create admin user: cd /var/www/crm && php artisan make:filament-user"
echo "  2. Access admin panel: http://YOUR_IP/admin"
