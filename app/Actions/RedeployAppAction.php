<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;

class RedeployAppAction
{
    public function handle(string $sitePath)
    {
        $token = getGithubAccessToken();
        // set env GITHUB_TOKEN to $token
        putenv("GITHUB_TOKEN={$token}");

        // git pull latest code
        $cmd = "cd {$sitePath} && git pull";

        return Process::run($cmd)->throw();
    }
}
