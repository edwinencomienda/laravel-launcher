<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class GetGithubRepoListAction
{
    public function handle()
    {
        $repos = [];
        $page = 1;
        $total = null;

        do {
            $response = Http::withToken(getGithubAccessToken())
                ->get("https://api.github.com/installation/repositories?per_page=100&page={$page}")
                ->json();

            $repos = array_merge($repos, $response['repositories'] ?? []);
            $total = $response['total_count'] ?? null;
            $page++;
        } while ($total && count($repos) < $total);

        return collect($repos)->map(fn ($repo) => [
            'id' => $repo['id'],
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'ssh_url' => $repo['ssh_url'],
            'default_branch' => $repo['default_branch'],
        ]);
    }
}
