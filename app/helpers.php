<?php

// convert the github url to a ssh url
if (! function_exists('convertGithubUrlToSshUrl')) {
    function convertGithubUrlToSshUrl(string $url): string
    {
        return str_replace('https://github.com/', 'git@github.com:', $url);
    }
}

if (! function_exists('isInServer')) {
    function isInServer(): bool
    {
        return file_exists('/home/raptor');
    }
}
