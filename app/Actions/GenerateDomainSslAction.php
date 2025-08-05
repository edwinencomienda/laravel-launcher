<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;

class GenerateDomainSslAction
{
    public function handle(array $domains)
    {
        $domainsString = implode(' -d ', $domains);

        $script = <<<BASH
        sudo certbot --nginx $domainsString --non-interactive --agree-tos --register-unsafely-without-email
        BASH;

        $result = Process::run($script)->throw();

        return $result->output();
    }
}
