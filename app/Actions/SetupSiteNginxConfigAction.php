<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;

class SetupSiteNginxConfigAction
{
    public function handle(
        string $siteRootDirectory,
        string $domain,
        int $port = 8082,
    ) {
        $nginxTemplate = <<<EOF
        server {
            listen {$port};
            listen [::]:{$port};
            server_name {$domain};
            root {$siteRootDirectory};

            index index.php index.html;

            client_max_body_size 25M;

            location / {
                try_files \$uri \$uri/ /index.php?\$query_string;
            }

            location ~ \.php$ {
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
                fastcgi_index index.php;
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            }

            location ~ /\.ht {
                deny all;
            }
        }
        EOF;

        $sudoPassword = 'raptor';

        // Create bash script for nginx operations
        $bashScript = <<<BASH
        #!/bin/bash

        # delete existing config
        rm -f /etc/nginx/sites-available/{$domain}
        rm -f /etc/nginx/sites-enabled/{$domain}

        # Create new nginx config
        cat > /etc/nginx/sites-available/{$domain} << 'EOF'
        {$nginxTemplate}
        EOF

        # Create symlink to enable site
        ln -sf /etc/nginx/sites-available/{$domain} /etc/nginx/sites-enabled/{$domain}

        # Test nginx configuration
        nginx -t

        # Restart nginx if config is valid
        if [ $? -eq 0 ]; then
            systemctl restart nginx
            echo "Nginx configuration updated and restarted successfully"
        else
            echo "Nginx configuration test failed"
            exit 1
        fi
        BASH;

        // Execute the bash script with sudo
        $result = Process::run("echo '{$sudoPassword}' | sudo -S bash -c '{$bashScript}'")->throw();

        return $result->output();
    }
}
