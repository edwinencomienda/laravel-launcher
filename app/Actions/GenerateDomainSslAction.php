<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;

class GenerateDomainSslAction
{
    public function handle(string $domain)
    {
        $checkScript = "test -f /etc/letsencrypt/live/$domain/fullchain.pem";
        if (Process::run($checkScript)->successful()) {
            return;
        }

        $script = <<<BASH
        sudo certbot --nginx -d $domain --non-interactive --agree-tos --register-unsafely-without-email
        BASH;

        $result = Process::run($script)->throw();

        return $result->output();
    }
}
