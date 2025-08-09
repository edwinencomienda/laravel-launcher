<?php

namespace App\Http\Controllers;

use App\Actions\RedeployAppAction;
use App\Enums\SettingsEnum;
use App\Models\Application;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GitHubAppController extends Controller
{
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
            Storage::put('github_app_config.json', json_encode($appConfig));

            return to_route('github.install');
        }

        return response()->json([
            'message' => 'Failed to create GitHub App',
            'error' => $response->json(),
        ], 500);
    }

    public function install()
    {
        $appConfig = Storage::get('github_app_config.json');
        $appConfig = json_decode($appConfig, true);
        $appSlug = $appConfig['slug'];

        return view('github-install', compact('appSlug'));
    }

    public function setup(Request $request)
    {
        Storage::put('github_installation_config.json', json_encode($request->all()));

        Setting::updateOrCreate([
            'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
        ], [
            'value->step' => 4,
        ]);

        return to_route('onboarding', [
            'github_install' => true,
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $appConfig = Storage::get('github_app_config.json');
        $appConfig = json_decode($appConfig, true);

        $webhookSecret = $appConfig['webhook_secret'];
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $computedSignature = 'sha256='.hash_hmac('sha256', $payload, $webhookSecret);

        if (hash_equals($signature, $computedSignature)) {
            $data = $request->json()->all();
            logger('webhook received', $data);

            // Check if repository exists in our applications
            if (isset($data['repository']['full_name'])) {
                $repoFullName = $data['repository']['full_name'];
                $application = Application::where('repo_name', $repoFullName)->first();

                if ($application) {
                    info("new update from app {$application->name}");
                    $redeployAppAction = new RedeployAppAction;
                    $redeployAppAction->handle($application->app_path);
                }
            }

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
