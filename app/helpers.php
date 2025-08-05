<?php

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
