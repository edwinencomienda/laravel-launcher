<?php

namespace App\Http\Controllers;

use App\Actions\GetGithubRepoListAction;
use App\Actions\PerformOnboardingAction;
use App\Enums\SettingsEnum;
use App\Models\Setting;
use App\Rules\FqdnRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    private function getOnboardingData()
    {
        $data = Setting::getByKey(SettingsEnum::CURRENT_ONBOARDING_DATA);

        return $data ? json_decode($data, true) : [];
    }

    public function index(Request $request)
    {
        // get ip address
        $ip = exec('hostname -I | awk \'{print $1}\'') ?: '127.0.0.1';
        $sshPublicKey = file_exists('/home/raptor/.ssh/id_rsa.pub')
            ? file_get_contents('/home/raptor/.ssh/id_rsa.pub')
            : 'n/a';
        $onboardingData = $this->getOnboardingData();

        $githubManifest = [
            'name' => 'Raptor Deploy',
            'url' => url('/'),
            'redirect_url' => url('/github/callback'),
            'setup_url' => route('github.setup'),
            'callback_urls' => [
                url('/github/callback'),
            ],
            'hook_attributes' => [
                'url' => config('app.github_webhook_url') ?? url('/github/webhook'),
            ],
            'public' => false,
            'default_permissions' => [
                'contents' => 'read',
            ],
            'default_events' => [
                'push',
            ],
        ];

        return Inertia::render('onboarding', [
            'ip' => $ip,
            'sshPublicKey' => $sshPublicKey,
            'onboardingData' => $onboardingData,
            'githubManifest' => $githubManifest,
            'query' => $request->query(),
            'repos' => Inertia::defer(fn () => (new GetGithubRepoListAction)->handle()),
        ]);
    }

    public function store(
        Request $request,
        PerformOnboardingAction $performOnboardingAction
    ) {
        $data = [];

        if ($request->step === 1) {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|max:255|email',
                'password' => 'required|string|max:255',
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->name' => $data['name'],
                'value->email' => $data['email'],
                'value->password' => bcrypt($data['password']),
                'value->step' => 2,
            ]);
        } elseif ($request->step === 2) {
            $data = $request->validate([
                'admin_domain' => ['required', 'string', 'max:255', new FqdnRule],
                'site_domain' => ['required', 'string', 'max:255', new FqdnRule],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::ADMIN_DOMAIN,
            ], [
                'value' => $data['admin_domain'],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::SITE_DOMAIN,
            ], [
                'value' => $data['site_domain'],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->step' => 3,
                'value->admin_domain' => $data['admin_domain'],
                'value->site_domain' => $data['site_domain'],
            ]);
        } elseif ($request->step === 4) {
            $data = $request->validate([
                'app_name' => 'required|string|max:255',
                'repo_name' => 'required|string|max:255',
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->app_name' => $data['app_name'],
                'value->repo_name' => $data['repo_name'],
            ]);

            try {
                $performOnboardingAction->handle();
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->step' => 5,
            ]);
        }

        return back();
    }
}
