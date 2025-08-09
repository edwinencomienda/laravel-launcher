#!/bin/sh

# set -e

# Configuration variables
CUSTOM_USER="raptor"
LINUX_USER_PASSWORD="raptor"
MYSQL_ROOT_PASSWORD="raptor"
MYSQL_USER_PASSWORD="raptor"
MYSQL_DEFAULT_DATABASE="raptor"

echo "Welcome to the Raptor setup script!"


IPV4=$(hostname -I | awk '{print $1}')

echo "Current IPv4: $IPV4"

# check user must be root otherwise exit
if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root."
    exit 1
fi

    # create default web user
if ! id "$CUSTOM_USER" &>/dev/null; then
    echo "Creating user '$CUSTOM_USER'..."
    adduser --disabled-password --gecos "" "$CUSTOM_USER"
    echo "$CUSTOM_USER:$LINUX_USER_PASSWORD" | chpasswd
    echo "Password set for user '$CUSTOM_USER'."

    # add user to sudo group
    usermod -aG sudo "$CUSTOM_USER"
    echo "User '$CUSTOM_USER' added to sudo group."
else
    echo "User '$CUSTOM_USER' already exists, skipping creation."
fi


# save the db password to user's home directory in /home/"$CUSTOM_USER"/"$CUSTOM_USER"
mkdir -p /home/"$CUSTOM_USER"/".$CUSTOM_USER"
echo "$CUSTOM_USER" > /home/"$CUSTOM_USER"/".$CUSTOM_USER"/db_username
echo "$MYSQL_USER_PASSWORD" > /home/"$CUSTOM_USER"/".$CUSTOM_USER"/db_password
echo "$MYSQL_DEFAULT_DATABASE" > /home/"$CUSTOM_USER"/".$CUSTOM_USER"/db_database

# ensure raptor user owns their home directory
echo "Setting ownership of /home/$CUSTOM_USER to $CUSTOM_USER user..."
chown -R "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"

# generate default ssh key if not exists
if [ ! -f /home/"$CUSTOM_USER"/.ssh/id_rsa ]; then
    mkdir -p /home/"$CUSTOM_USER"/.ssh
    ssh-keygen -f /home/"$CUSTOM_USER"/.ssh/id_rsa -t ed25519 -N '' -C "$CUSTOM_USER@$(hostname)"

    # copy root authorized_keys to new user
    cp /root/.ssh/authorized_keys /home/"$CUSTOM_USER"/.ssh/authorized_keys

    # set ownership and permissions
    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"/.ssh
    chmod 600 /home/"$CUSTOM_USER"/.ssh/id_rsa
    chmod 644 /home/"$CUSTOM_USER"/.ssh/authorized_keys
    chmod 700 /home/"$CUSTOM_USER"/.ssh
else
    echo "SSH key for $CUSTOM_USER already exists, skipping generation."
fi


# add repositories for php and nginx
echo "Adding repositories for PHP and Nginx..."
add-apt-repository -y -n ppa:ondrej/nginx
add-apt-repository -y -n ppa:ondrej/php

echo "Updating package lists..."
apt-get update


# Add github, bitbucket, gitlab to root's known hosts if not already present
if [ ! -f /root/.ssh/known_hosts ]; then
    mkdir -p /root/.ssh
    touch /root/.ssh/known_hosts
    chmod 600 /root/.ssh/known_hosts

    for host in github.com bitbucket.org gitlab.com; do
        ssh-keyscan -H $host >> /root/.ssh/known_hosts
    done
else
    echo "Root known_hosts file already exists, skipping addition."
fi

# Copy known_hosts to custom user if not already present
if [ ! -f /home/"$CUSTOM_USER"/.ssh/known_hosts ]; then
    mkdir -p /home/"$CUSTOM_USER"/.ssh
    cp /root/.ssh/known_hosts /home/"$CUSTOM_USER"/.ssh/known_hosts
    chown "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"/.ssh/known_hosts
    chmod 600 /home/"$CUSTOM_USER"/.ssh/known_hosts
else
    echo "Known hosts file already exists for $CUSTOM_USER, skipping copy."
fi

# Install php and fpm
if ! dpkg -l | grep -q php8.3-fpm; then
    echo "Installing PHP packages..."
    apt-get install -y \
      php8.3 \
      php8.3-fpm php8.3-cli php8.3-dev \
      php8.3-pgsql php8.3-sqlite3 php8.3-gd php8.3-curl \
      php8.3-imap php8.3-mysql php8.3-mbstring \
      php8.3-xml php8.3-zip php8.3-bcmath php8.3-soap \
      php8.3-intl php8.3-readline php8.3-gmp \
      php8.3-redis php8.3-msgpack php8.3-igbinary

    # update default user of php-cli
    sed -i "s/user = www-data/user = $CUSTOM_USER/" /etc/php/8.3/cli/php.ini
    sed -i "s/group = www-data/group = $CUSTOM_USER/" /etc/php/8.3/cli/php.ini

    # update default user of php-fpm
    sed -i "s/user = www-data/user = $CUSTOM_USER/" /etc/php/8.3/fpm/pool.d/www.conf
    sed -i "s/group = www-data/group = $CUSTOM_USER/" /etc/php/8.3/fpm/pool.d/www.conf

    systemctl restart php8.3-fpm
else
    echo "PHP packages already installed."
fi

if ! dpkg -l | grep -q nginx; then
    echo "Installing Nginx..."
    apt-get install -y nginx
    sed -i "s/user www-data;/user $CUSTOM_USER;/" /etc/nginx/nginx.conf

    # allow user to manage nginx
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/service nginx *" >> /etc/sudoers.d/nginx
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/nginx -t" >> /etc/sudoers.d/nginx
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/nginx -s reload" >> /etc/sudoers.d/nginx

    # change the ownership of the sites-available and sites-enabled directories
    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /etc/nginx/sites-available/
    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /etc/nginx/sites-enabled/

    systemctl restart nginx
else
    echo "Nginx already installed."
fi

# install certbot
if ! command -v certbot &> /dev/null; then
    echo "Installing Certbot..."
    apt-get install -y certbot python3-certbot-nginx

    # add sudoers
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/bin/certbot *" > /etc/sudoers.d/certbot

    echo "Certbot installed."
else
    echo "Certbot already installed."
fi


# install unzip
if ! command -v unzip &> /dev/null; then
    echo "Installing Unzip..."
    apt-get install -y unzip
    echo "Unzip installed."
else
    echo "Unzip already installed."
fi

# allow user to manage ufw
if ! grep -q "/usr/sbin/ufw" /etc/sudoers.d/ufw 2>/dev/null; then
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/ufw *" > /etc/sudoers.d/ufw
    echo "Added $CUSTOM_USER permission to manage ufw."
else
    echo "$CUSTOM_USER already has permission to manage ufw."
fi


# install composer
if [ ! -f /usr/local/bin/composer ]; then
  echo "Installing Composer..."
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer
  chmod +x /usr/local/bin/composer

  echo "$CUSTOM_USER ALL=NOPASSWD: /usr/local/bin/composer *" > /etc/sudoers.d/composer
  chmod 440 /etc/sudoers.d/composer

  echo "export COMPOSER_HOME=/home/$CUSTOM_USER/.composer" >> /home/$CUSTOM_USER/.bashrc
  echo "source /home/$CUSTOM_USER/.bashrc" >> /home/$CUSTOM_USER/.bashrc
else
  echo "Composer already installed."
fi

# install nodejs and npm
if ! command -v node &> /dev/null; then
    echo "Installing Node.js and npm..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
    apt-get install -y nodejs
    echo "Updating npm to latest version..."
    npm install -g npm@latest

    npm install -g yarn
    npm install -g pnpm
    npm install -g bun
else
    echo "Node.js already installed."
    echo "Updating npm to latest version..."
    npm install -g npm@latest
fi

# install mysql
if ! dpkg -l | grep -q mysql-server; then
    echo "Installing MySQL..."

    echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
    echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
    apt-get install -y mysql-server

    # create mysql user
    echo "Creating MySQL user '$CUSTOM_USER'..."
    mysql -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE USER '$CUSTOM_USER'@'localhost' IDENTIFIED BY '$MYSQL_USER_PASSWORD'; GRANT ALL PRIVILEGES ON *.* TO '$CUSTOM_USER'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"

    # disable password expiration
    echo "default_password_lifetime = 0" >> /etc/mysql/mysql.conf.d/mysqld.cnf
    # configure max connections
    RAM=$(awk '/^MemTotal:/{printf "%3.0f", $2 / (1024 * 1024)}' /proc/meminfo)
    MAX_CONNECTIONS=$(( 70 * RAM ))
    REAL_MAX_CONNECTIONS=$(( MAX_CONNECTIONS>70 ? MAX_CONNECTIONS : 100 ))
    echo "max_connections=${REAL_MAX_CONNECTIONS}" >> /etc/mysql/mysql.conf.d/mysqld.cnf

    # create default database
    mysql -e "CREATE DATABASE $MYSQL_DEFAULT_DATABASE CHARACTER SET utf8 COLLATE utf8_unicode_ci;"

    # allow user to manage mysql
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/service mysql restart" > /etc/sudoers.d/mysql-restart
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/service mysql stop" >> /etc/sudoers.d/mysql-restart
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/service mysql start" >> /etc/sudoers.d/mysql-restart
else
    echo "MySQL already installed."
fi

# configure firewall
if ! ufw status | grep -q "Status: active"; then
    echo "Configuring UFW firewall..."
    ufw --force enable
    ufw allow 22/tcp   # SSH
    ufw allow 80/tcp   # HTTP
    ufw allow 8081/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS
    ufw reload
    echo "Firewall configured and enabled."
else
    echo "UFW firewall already active."
    # Ensure our ports are allowed even if UFW was already active
    ufw allow 22/tcp 2>/dev/null || true
    ufw allow 80/tcp 2>/dev/null || true
    ufw allow 8081/tcp 2>/dev/null || true
    ufw allow 443/tcp 2>/dev/null || true
fi


# install supervisord
if ! dpkg -l | grep -q supervisor || [ "$(stat -c %U /etc/supervisor/conf.d 2>/dev/null)" != "$CUSTOM_USER" ]; then
    echo "Installing Supervisor..."
    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor

    # allow user to manage supervisor
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/bin/supervisorctl *" > /etc/sudoers.d/supervisor

    # restart supervisor to apply configuration
    systemctl restart supervisor

    # set raptor as owner of supervisor conf.d directory
    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /etc/supervisor/conf.d/
    chmod 755 /etc/supervisor/conf.d/
    echo "$CUSTOM_USER user can now manage supervisor configuration files."
else
    echo "Supervisor already installed and configured."
fi

# remove the default nginx site
rm -f /etc/nginx/sites-available/default
rm -f /etc/nginx/sites-enabled/default

# create the new default site if not exists
if [ ! -f /etc/nginx/sites-available/default ]; then
cat <<EOF > /etc/nginx/sites-available/default
server {
    listen 8081;
    server_name $IPV4;
    root /home/$CUSTOM_USER/raptor/public;
    index index.php index.html;

    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_page 404 /index.php;
}
EOF

    # clone the raptor repository and setup
    git clone https://github.com/edwinencomienda/laravel-launcher.git /home/$CUSTOM_USER/raptor
    cd /home/$CUSTOM_USER/raptor && composer install
    cd /home/$CUSTOM_USER/raptor && cp .env.example .env
    php /home/$CUSTOM_USER/raptor/artisan key:generate
    php /home/$CUSTOM_USER/raptor/artisan migrate

    # build the assets
    bun install
    bun run build

    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /home/$CUSTOM_USER/raptor
    chmod -R 755 /home/$CUSTOM_USER/raptor

    # restart nginx and enable the default site
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
    nginx -t && nginx -s reload

    # restart supervisor
    supervisorctl restart all
else
    echo "Default site already exists, skipping creation."
fi

if [ ! -f /etc/supervisor/conf.d/raptor.conf ]; then
cat >/etc/supervisor/conf.d/raptor.conf <<EOF
[program:raptor-queue]
command=php /home/$CUSTOM_USER/raptor/artisan queue:work --sleep=3 --tries=3
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
user=$CUSTOM_USER
numprocs=1
redirect_stderr=true
stdout_logfile=/home/$CUSTOM_USER/raptor/storage/logs/queue-worker.log
stopwaitsecs=3600
stopsignal=SIGTERM
stopasgroup=true
killasgroup=true
environment=HOME="/home/$CUSTOM_USER"
EOF

chown -R "$CUSTOM_USER":"$CUSTOM_USER" /etc/supervisor/conf.d/raptor.conf
chmod 755 /etc/supervisor/conf.d/raptor.conf

systemctl restart supervisor

echo "Supervisor config raptor.conf created and restarted."
else
    echo "Supervisor config raptor.conf already exists, skipping creation."
fi



cat <<'EOF'
 ____             _
|  _ \ __ _ _ __ | |_ ___  _ __
| |_) / _` | '_ \| __/ _ \| '__|
|  _ < (_| | |_) | || (_) | |
|_| \_\__,_| .__/ \__\___/|_|
           |_|
EOF
echo "=================================================="
echo "🚀  Setup complete! 🚀"
echo "=================================================="
echo "🔑  System Credentials:"
echo "🔑  Sudo Password: $LINUX_USER_PASSWORD"
echo "🔑  DB Password: $MYSQL_USER_PASSWORD"
echo "Please make sure to copy these credentials now as they will not be shown again."
echo "=================================================="
echo "Starting onboarding process..."
echo "Visit the onboarding URL: http://$IPV4:8081/onboarding"

echo "Setup complete! 🚀"
