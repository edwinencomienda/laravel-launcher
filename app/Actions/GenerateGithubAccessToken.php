<?php

namespace App\Actions;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

class GenerateGithubAccessToken
{
    public function handle(
        string $appId,
        string $installationId,
        string $privateKey,
    ) {
        // Create JWT
        $issuedAt = time();
        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + (10 * 60), // Token valid for 10 minutes
            'iss' => $appId,
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');

        // Request installation access token
        $response = Http::withHeaders([
            'Authorization' => "Bearer $jwt",
            'Accept' => 'application/vnd.github+json',
        ])->post("https://api.github.com/app/installations/{$installationId}/access_tokens");

        if ($response->successful()) {
            return $response->json()['token'];
        }

        throw new \Exception('Could not generate access token: '.$response->body());
    }
}
