<?php

namespace App\Actions;

use App\Services\SshService;

class CreateNginxSiteAction
{
    public function handle(
        string $rootPath,
        string $domain,
        int $port = 80,
    ) {
        $nginx = <<<EOF
        server {
            listen {$port};
            server_name {$domain};
            root {$rootPath};
            index index.php index.html;

            client_max_body_size 25M;

            #access_log /var/log/nginx/access.log;
            #error_log /var/log/nginx/error.log;

            location ~ \.php$ {
                fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
                fastcgi_index index.php;
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            }

            location / {
                try_files \$uri \$uri/ /index.php?\$query_string;
            }
        }
        EOF;

        $ssh = new SshService(
            host: '5.223.75.35',
            user: 'raptor',
        );

        $ssh->connect();

        $output = $ssh->runCommand(
            <<<BASH
        set -e

        # delete if exists
        rm -f /etc/nginx/sites-available/$domain
        rm -f /etc/nginx/sites-enabled/$domain

        cat <<'EOF' > /etc/nginx/sites-available/$domain
        $nginx
        EOF

        ln -s /etc/nginx/sites-available/$domain /etc/nginx/sites-enabled/$domain
        
        # check and reload nginx
        sudo nginx -t && sudo nginx -s reload
        BASH
        );

        return $output;
    }
}
