<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GitHubAppController extends Controller
{
    public function redirect()
    {
        $manifest = [
            'name' => 'Raptor App',
            'url' => url('/'),
            'redirect_url' => url('/github/callback'),
            'setup_url' => url('/github/setup'),
            'callback_urls' => [
                url('/github/callback'),
            ],
            'hook_attributes' => [
                'url' => url('/github/webhook'),
            ],
            'public' => false,
            'default_permissions' => [
                'contents' => 'read',
            ],
            'default_events' => [
                'push',
            ],
        ];

        return view('github-redirect', compact('manifest'));
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (! $code) {
            return response()->json(['error' => 'No code provided'], 400);
        }

        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])
            ->withBody(null)
            ->post("https://api.github.com/app-manifests/{$code}/conversions");

        if ($response->successful()) {
            $appConfig = $response->json();
            Storage::put('github_response.json', json_encode($appConfig));

            return response()->json(['message' => 'GitHub App created successfully', 'config' => $appConfig]);
        }

        return response()->json([
            'message' => 'Failed to create GitHub App',
            'error' => $response->json(),
        ], 500);
    }

    public function install()
    {
        $appConfig = Storage::get('github_response');
        $appConfig = json_decode($appConfig, true);
        $appSlug = $appConfig['slug'];

        return redirect("https://github.com/apps/{$appSlug}/installations/new");
    }

    public function setup(Request $request)
    {
        Storage::put('github_install_response.json', json_encode($request->all()));

        return 'ok';
    }

    public function handleWebhook(Request $request)
    {
        $appConfig = Storage::get('github_response.json');
        $appConfig = json_decode($appConfig, true);

        $webhookSecret = $appConfig['webhook_secret'];
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $computedSignature = 'sha256='.hash_hmac('sha256', $payload, $webhookSecret);

        if (hash_equals($signature, $computedSignature)) {
            $data = $request->json()->all();
            logger('webhook received', $data);

            return response()->json(['message' => 'Webhook processed']);
        } else {
            logger('webhook invalid signature');
        }

        return response()->json(['error' => 'Invalid signature'], 401);
    }

    // TODO1: generate access token for cloning an app
    // TODO2: clone the app on the server
    // TODO3: list down repositories
}
