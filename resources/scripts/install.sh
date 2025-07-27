#!/bin/bash

# Configuration variables
LINUX_USER_PASSWORD="raptor"
MYSQL_ROOT_PASSWORD="raptor"
MYSQL_USER_PASSWORD="raptor"

echo "Welcome to the Raptor setup script!"

echo "Current IPv4: $(curl -s ifconfig.me)"


# create default web user
if ! id "raptor" &>/dev/null; then
    echo "Creating user 'raptor'..."
    adduser --disabled-password --gecos "" raptor
    echo "raptor:$LINUX_USER_PASSWORD" | chpasswd
    echo "Password set for user 'raptor'."
else
    echo "User 'raptor' already exists, skipping creation."
fi

# ensure raptor user owns their home directory
echo "Setting ownership of /home/raptor to raptor user..."
chown -R raptor:raptor /home/raptor

# generate default ssh key if not exists
if [ ! -f /home/raptor/.ssh/id_rsa ]; then
    mkdir -p /home/raptor/.ssh
    ssh-keygen -f /home/raptor/.ssh/id_rsa -t ed25519 -N '' -C "raptor@$(hostname)"
    chown -R raptor:raptor /home/raptor/.ssh
    chmod 700 /home/raptor/.ssh/id_rsa
else
    echo "SSH key for raptor already exists, skipping generation."
fi

# add github, bitbucket, gitlab to known hosts if not already present
if [ ! -f /home/raptor/.ssh/known_hosts ]; then
    mkdir -p /home/raptor/.ssh
    touch /home/raptor/.ssh/known_hosts
    chmod 600 /home/raptor/.ssh/known_hosts
    chown raptor:raptor /home/raptor/.ssh/known_hosts
fi

for host in github.com bitbucket.org gitlab.com; do
    if ! grep -q "$host" /home/raptor/.ssh/known_hosts; then
        ssh-keyscan -H $host >> /home/raptor/.ssh/known_hosts
    fi
done

chown raptor:raptor /home/raptor/.ssh/known_hosts

# add user to sudo group
if ! groups raptor | grep -q sudo; then
    echo "Adding raptor to sudo group..."
    usermod -aG sudo raptor
else
    echo "User 'raptor' is already in sudo group."
fi

# install unzip
if ! command -v unzip &> /dev/null; then
    echo "Installing Unzip..."
    apt-get update
    apt-get install -y unzip
    echo "Unzip installed."
else
    echo "Unzip already installed."
fi

# install php 
if ! grep -q "ondrej/php" /etc/apt/sources.list.d/* 2>/dev/null; then
    echo "Adding PHP repository..."
    add-apt-repository ppa:ondrej/php -y
    apt update
else
    echo "PHP repository already added."
fi

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
else
    echo "PHP packages already installed."
fi

# install composer
if [ ! -f /usr/local/bin/composer ]; then
  echo "Installing Composer..."
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer

  echo "raptor ALL=(root) NOPASSWD: /usr/local/bin/composer self-update*" > /etc/sudoers.d/composer
else
  echo "Composer already installed."
fi

# update PHP memory limit and upload size
if ! grep -q "memory_limit = 512M" /etc/php/8.3/fpm/php.ini; then
    echo "Updating PHP configuration..."
    sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/8.3/fpm/php.ini
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 128M/" /etc/php/8.3/fpm/php.ini
    sed -i "s/post_max_size = .*/post_max_size = 128M/" /etc/php/8.3/fpm/php.ini
    systemctl restart php8.3-fpm
else
    echo "PHP configuration already updated."
fi

# Update PHP-FPM Configuration
if ! grep -q "user = raptor" /etc/php/8.3/fpm/pool.d/www.conf; then
    echo "Updating PHP-FPM configuration..."
    sed -i "s/user = www-data/user = raptor/" /etc/php/8.3/fpm/pool.d/www.conf
    sed -i "s/group = www-data/group = raptor/" /etc/php/8.3/fpm/pool.d/www.conf
    sed -i "s/^pm.max_children.*/pm.max_children = 20/" /etc/php/8.3/fpm/pool.d/www.conf
    systemctl restart php8.3-fpm
else
    echo "PHP-FPM configuration already updated."
fi

if ! grep -q "^raptor ALL=NOPASSWD: /usr/sbin/service mysql restart" /etc/sudoers.d/mysql-restart 2>/dev/null; then
  echo "raptor ALL=NOPASSWD: /usr/sbin/service mysql restart" > /etc/sudoers.d/mysql-restart
fi
if ! grep -q "^raptor ALL=NOPASSWD: /usr/sbin/service php8.3-fpm restart" /etc/sudoers.d/php-fpm-restart 2>/dev/null; then
  echo "raptor ALL=NOPASSWD: /usr/sbin/service php8.3-fpm restart" > /etc/sudoers.d/php-fpm-restart
fi
if ! grep -q "^raptor ALL=NOPASSWD: /usr/sbin/service supervisor restart" /etc/sudoers.d/supervisor-restart 2>/dev/null; then
  echo "raptor ALL=NOPASSWD: /usr/sbin/service supervisor restart" > /etc/sudoers.d/supervisor-restart
fi

# install nodejs and npm
if ! command -v node &> /dev/null; then
    echo "Installing Node.js and npm..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
    apt-get install -y nodejs
    echo "Updating npm to latest version..."
    npm install -g npm@latest
else
    echo "Node.js already installed."
    echo "Updating npm to latest version..."
    npm install -g npm@latest
fi

# install mysql
if ! dpkg -l | grep -q mysql-server; then
    echo "Installing MySQL..."
    echo "mysql-community-server mysql-community-server/root-pass password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
    echo "mysql-community-server mysql-community-server/re-root-pass password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
    apt-get install -y mysql-community-server
    apt-get install -y mysql-server
else
    echo "MySQL already installed."
fi

# create mysql user
if ! mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT User FROM mysql.user WHERE User='raptor' AND Host='localhost';" 2>/dev/null | grep -q raptor; then
    echo "Creating MySQL user 'raptor'..."
    mysql -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE USER 'raptor'@'localhost' IDENTIFIED BY '$MYSQL_USER_PASSWORD'; GRANT ALL PRIVILEGES ON *.* TO 'raptor'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"
else
    echo "MySQL user 'raptor' already exists."
fi

# install nginx
if ! dpkg -l | grep -q nginx; then
    echo "Installing Nginx..."
    apt-get install -y nginx
    systemctl enable nginx
    systemctl start nginx
else
    echo "Nginx already installed."
fi

# Configure Primary Nginx Settings
if ! grep -q "user raptor;" /etc/nginx/nginx.conf; then
    echo "Configuring Nginx..."
    sed -i "s/user www-data;/user raptor;/" /etc/nginx/nginx.conf
    sed -i "s/worker_processes.*/worker_processes auto;/" /etc/nginx/nginx.conf
    sed -i "s/# multi_accept.*/multi_accept on;/" /etc/nginx/nginx.conf
    sed -i "s/# server_names_hash_bucket_size.*/server_names_hash_bucket_size 128;/" /etc/nginx/nginx.conf

    service nginx restart
else
    echo "Nginx configuration already updated."
fi

# install certbot
if ! command -v certbot &> /dev/null; then
    echo "Installing Certbot..."
    apt-get update
    apt-get install -y certbot python3-certbot-nginx
else
    echo "Certbot already installed."
fi

# Allow raptor user to manage certbot
if ! grep -q "^raptor ALL=NOPASSWD: /usr/bin/certbot" /etc/sudoers.d/certbot 2>/dev/null; then
    echo "raptor ALL=NOPASSWD: /usr/bin/certbot *" > /etc/sudoers.d/certbot
    echo "raptor ALL=NOPASSWD: /usr/sbin/service nginx reload" >> /etc/sudoers.d/certbot
    echo "raptor ALL=NOPASSWD: /usr/sbin/service nginx restart" >> /etc/sudoers.d/certbot
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

# Create default web directory
if [ ! -d /home/raptor/default ]; then
    echo "Creating default web directory..."
    mkdir -p /home/raptor/default
    chown -R raptor:raptor /home/raptor/default
    chmod 755 /home/raptor/default
fi

# Create default index.php
if [ ! -f /home/raptor/default/index.php ]; then
    echo "Creating default index.php..."
    cat > /home/raptor/default/index.php << 'EOF'
<?php
echo "raptor configured successfully";
EOF
    chown raptor:raptor /home/raptor/default/index.php
    chmod 644 /home/raptor/default/index.php
fi

# Configure default site to serve index.php
if [ ! -f /etc/nginx/sites-available/default ] || ! grep -q "index index.php" /etc/nginx/sites-available/default; then
    echo "Configuring default Nginx site..."
    cat > /etc/nginx/sites-available/default << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    root /home/raptor/default;
    index index.php index.html index.htm;
    
    server_name _;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF
    
    # Enable the site and restart nginx
    ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/
    service nginx restart
else
    echo "Default Nginx site already configured."
fi

# install supervisord
if ! dpkg -l | grep -q supervisor; then
    echo "Installing Supervisor..."
    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
else
    echo "Supervisor already installed."
fi

# Configure supervisord
if [ ! -f /etc/supervisor/conf.d/raptor.conf ]; then
    echo "Configuring Supervisor..."
    cat > /etc/supervisor/conf.d/raptor.conf << 'EOF'
[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700
chown=root:root

[supervisord]
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
childlogdir=/var/log/supervisor

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[include]
files = /etc/supervisor/conf.d/*.conf
EOF
    
    # Allow raptor user to control supervisor
    echo "raptor ALL=NOPASSWD: /usr/bin/supervisorctl *" > /etc/sudoers.d/supervisor
    
    # Restart supervisor to apply configuration
    systemctl restart supervisor
else
    echo "Supervisor already configured."
fi

# Allow raptor user to manage supervisor conf files
if [ "$(stat -c %U /etc/supervisor/conf.d 2>/dev/null)" != "raptor" ]; then
    echo "Setting raptor as owner of supervisor conf.d directory..."
    chown -R raptor:raptor /etc/supervisor/conf.d/
    chmod 755 /etc/supervisor/conf.d/
    echo "Raptor user can now manage supervisor configuration files."
else
    echo "Supervisor conf.d directory already owned by raptor."
fi

# create directory for raptor
if [ ! -d /home/raptor/raptor ]; then
    echo "Creating raptor directory..."
    mkdir -p /home/raptor/raptor
    chmod 755 /home/raptor/raptor
else
    echo "Raptor directory already exists."
fi


# clone raptor from github
if [ ! -d /home/raptor/raptor/.git ]; then
    echo "Cloning raptor from github..."
    git clone https://github.com/edwinencomienda/laravel-launcher.git /home/raptor/raptor
    echo "Raptor cloned from github."
else
    echo "Raptor already cloned from github."
fi

chown -R raptor:raptor /home/raptor/raptor

# install bunjs as raptor user
if ! command -v bun &> /dev/null; then
    echo "Installing Bun as raptor..."
    sudo -i -u raptor bash -c "curl -fsSL https://bun.sh/install | bash && source /home/raptor/.bashrc"
    echo "Bun installed."
else
    echo "Bun already installed."
fi


# Display database credentials
echo ""
echo "=================================================="
echo "        SYSTEM CREDENTIALS"
echo "  ⚠️  COPY THESE NOW - SHOWN ONLY ONCE!"
echo "=================================================="
echo "Sudo User: raptor"
echo "Sudo Password: $LINUX_USER_PASSWORD"
echo ""
echo "MySQL Database User: raptor"
echo "MySQL Database Password: $MYSQL_USER_PASSWORD"
echo "=================================================="
echo ""

echo "Setup complete!"
