<?php

namespace App\Actions;

use App\Services\SshService;

class RemoveNginxSiteAction
{
    public function handle(string $domain)
    {
        $ssh = new SshService(
            host: '5.223.75.35',
            user: 'raptor',
        );

        $ssh->connect();

        $output = $ssh->runCommand(<<<BASH
        rm -f /etc/nginx/sites-available/$domain
        rm -f /etc/nginx/sites-enabled/$domain
        sudo nginx -t && sudo nginx -s reload
        BASH);

        return $output;
    }
}
