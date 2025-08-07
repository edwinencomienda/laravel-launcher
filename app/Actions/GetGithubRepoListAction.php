<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class GetGithubRepoListAction
{
    public function handle()
    {
        $response = Http::withToken(getGithubAccessToken())
            ->get('https://api.github.com/installation/repositories')
            ->json();

        return collect($response['repositories'])->map(function ($repo) {
            return [
                'id' => $repo['id'],
                'name' => $repo['name'],
                'full_name' => $repo['full_name'],
                'ssh_url' => $repo['ssh_url'],
            ];
        });
    }
}
