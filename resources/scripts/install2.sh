#!/bin/sh

set -e

# Configuration variables
CUSTOM_USER="raptor"
LINUX_USER_PASSWORD="raptor"
MYSQL_ROOT_PASSWORD="raptor"
MYSQL_USER_PASSWORD="raptor"

echo "Welcome to the Raptor setup script!"

echo "Current IPv4: $(curl -s ifconfig.me)"

# create default web user
if ! id "$CUSTOM_USER" &>/dev/null; then
    echo "Creating user '$CUSTOM_USER'..."
    adduser --disabled-password --gecos "" "$CUSTOM_USER"
    echo "$CUSTOM_USER:$LINUX_USER_PASSWORD" | chpasswd
    echo "Password set for user '$CUSTOM_USER'."
else
    echo "User '$CUSTOM_USER' already exists, skipping creation."
fi

# ensure raptor user owns their home directory
echo "Setting ownership of /home/$CUSTOM_USER to $CUSTOM_USER user..."
chown -R "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"

# generate default ssh key if not exists
if [ ! -f /home/"$CUSTOM_USER"/.ssh/id_rsa ]; then
    mkdir -p /home/"$CUSTOM_USER"/.ssh
    ssh-keygen -f /home/"$CUSTOM_USER"/.ssh/id_rsa -t ed25519 -N '' -C "$CUSTOM_USER@$(hostname)"
    chown -R "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"/.ssh
    chmod 700 /home/"$CUSTOM_USER"/.ssh/id_rsa
else
    echo "SSH key for $CUSTOM_USER already exists, skipping generation."
fi

# add github, bitbucket, gitlab to known hosts if not already present
if [ ! -f /home/"$CUSTOM_USER"/.ssh/known_hosts ]; then
    mkdir -p /home/"$CUSTOM_USER"/.ssh
    touch /home/"$CUSTOM_USER"/.ssh/known_hosts
    chmod 600 /home/"$CUSTOM_USER"/.ssh/known_hosts

    for host in github.com bitbucket.org gitlab.com; do
        ssh-keyscan -H $host >> /home/"$CUSTOM_USER"/.ssh/known_hosts
    done

    chown "$CUSTOM_USER":"$CUSTOM_USER" /home/"$CUSTOM_USER"/.ssh/known_hosts
else
    echo "Known hosts file already exists for $CUSTOM_USER, skipping addition."
fi

# add user to sudo group
if ! groups "$CUSTOM_USER" | grep -q sudo; then
    echo "Adding $CUSTOM_USER to sudo group..."
    usermod -aG sudo "$CUSTOM_USER"
else
    echo "User '$CUSTOM_USER' is already in sudo group."
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

# allow user to manage ufw
if ! grep -q "/usr/sbin/ufw" /etc/sudoers.d/ufw 2>/dev/null; then
    echo "$CUSTOM_USER ALL=NOPASSWD: /usr/sbin/ufw *" > /etc/sudoers.d/ufw
    echo "Added $CUSTOM_USER permission to manage ufw."
else
    echo "$CUSTOM_USER already has permission to manage ufw."
fi

# install frankenphp
if [ ! -f /usr/local/bin/frankenphp ]; then
    echo "Installing FrankenPHP..."
    curl https://frankenphp.dev/install.sh | sh
    mv frankenphp /usr/local/bin/
    # add to sudoers.d/frankenphp
    echo "$CUSTOM_USER ALL=(root) NOPASSWD: /usr/local/bin/frankenphp" > /etc/sudoers.d/frankenphp
else
    echo "FrankenPHP already installed."
fi

# install php binary from https://static-php.dev/en/
if [ ! -f /usr/local/bin/php ]; then
    echo "Installing PHP Binary..."
    curl -sSL https://dl.static-php.dev/static-php-cli/common/php-8.4.8-cli-linux-x86_64.tar.gz | tar -xzf - -C /usr/local/bin/
    # rm -rf /usr/local/bin/php-8.4.8-cli-linux-x86_64.tar.gz

    for user in "$CUSTOM_USER" root; do
        echo "export PHP_BINARY=/usr/local/bin/php" >> /home/$user/.bashrc 2>/dev/null || echo "export PHP_BINARY=/usr/local/bin/php" >> /root/.bashrc
        echo "export PATH=/usr/local/bin:\$PATH" >> /home/$user/.bashrc 2>/dev/null || echo "export PATH=/usr/local/bin:\$PATH" >> /root/.bashrc
    done

    echo "PHP Binary installed successfully."
else
    echo "PHP Binary already installed, skipping installation."
fi

# install composer
if [ ! -f /usr/local/bin/composer ]; then
  echo "Installing Composer..."
  curl -sS https://getcomposer.org/installer | php
  mv composer.phar /usr/local/bin/composer

  echo "$CUSTOM_USER ALL=(root) NOPASSWD: /usr/local/bin/composer self-update*" > /etc/sudoers.d/composer
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

# Display database credentials
echo ""
echo "=================================================="
echo "        SYSTEM CREDENTIALS"
echo "  ⚠️  COPY THESE NOW - SHOWN ONLY ONCE!"
echo "=================================================="
echo "Sudo User: $CUSTOM_USER"
echo "Sudo Password: $LINUX_USER_PASSWORD"
echo ""
echo "MySQL Database User: $CUSTOM_USER"
echo "MySQL Database Password: $MYSQL_USER_PASSWORD"
echo "=================================================="
echo ""

echo "Setup complete!"
