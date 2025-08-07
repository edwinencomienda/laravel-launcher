<?php

use App\Actions\GenerateGithubAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

// convert the github url to a ssh url
if (! function_exists('convertGithubUrlToSshUrl')) {
    function convertGithubUrlToSshUrl(string $url): string
    {
        return 'git@github.com:'.preg_replace('#^https://github.com/#', '', rtrim($url, '.git'));
    }
}

if (! function_exists('isInServer')) {
    function isInServer(): bool
    {
        return file_exists('/home/raptor');
    }
}

if (! function_exists('getGithubAccessToken')) {
    function getGithubAccessToken(): string
    {
        return Cache::remember('github_access_token', 60 * 5, function () {
            $githubConfig = json_decode(Storage::get('github_app_config.json'), true);
            $installationId = json_decode(Storage::get('github_installation_config.json'), true)['installation_id'] ?? null;

            return (new GenerateGithubAccessToken)->handle(
                appId: $githubConfig['id'],
                installationId: $installationId,
                privateKey: $githubConfig['pem'],
            );
        });
    }
}
