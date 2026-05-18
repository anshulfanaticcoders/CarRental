#!/usr/bin/env bash
# Ubuntu 22 one-shot provisioning script for vrooem.com.
# Run as: sudo bash provision-ubuntu.sh
# Installs PHP 8.3 + MySQL 8 + Nginx + Supervisor + Composer + Node 20.
# Does NOT clone the repo or fill the .env — you do that in the next step.

set -euo pipefail

echo "==> Vrooem server provisioning starting…"

if [[ $EUID -ne 0 ]]; then
  echo "This script must be run as root (use sudo)."
  exit 1
fi

echo "==> [1/8] apt update + upgrade"
apt update
apt upgrade -y

echo "==> [2/8] adding PHP 8.3 PPA"
apt install -y software-properties-common ca-certificates lsb-release apt-transport-https
add-apt-repository -y ppa:ondrej/php
apt update

echo "==> [3/8] installing PHP 8.3 + extensions"
apt install -y \
  php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-gd php8.3-bcmath php8.3-zip php8.3-intl php8.3-redis \
  php8.3-opcache php8.3-soap

echo "==> [4/8] installing MySQL 8"
apt install -y mysql-server
systemctl enable mysql

echo "==> [5/8] installing Nginx + Supervisor + Certbot"
apt install -y nginx supervisor certbot python3-certbot-nginx ufw fail2ban

echo "==> [6/8] installing Composer"
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
  chmod +x /usr/local/bin/composer
fi

echo "==> [7/8] installing Node 20 LTS + git"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt install -y nodejs
fi
apt install -y git unzip

echo "==> [8/8] firewall (ports 22, 80, 443 only)"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Make /var/www writable for deploys
mkdir -p /var/www
chown www-data:www-data /var/www

cat <<EOF

==> PROVISIONING DONE.

Versions installed:
  PHP:       $(php -v | head -1)
  Composer:  $(composer --version)
  Node:      $(node -v)
  MySQL:     $(mysql --version)
  Nginx:     $(nginx -v 2>&1)

Next steps:
  1. Run 'sudo mysql_secure_installation' and set a root password.
  2. Clone the repo into /var/www/CarRental.
  3. Continue with docs/ops/production-server-setup.md from step 3.

EOF
