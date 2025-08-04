<?php

namespace App\Http\Controllers;

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

    public function index()
    {
        // get ip address
        $ip = exec('hostname -I | awk \'{print $1}\'') ?: '127.0.0.1';
        $sshPublicKey = file_exists('/home/raptor/.ssh/id_rsa.pub')
            ? file_get_contents('/home/raptor/.ssh/id_rsa.pub')
            : null;
        $onboardingData = $this->getOnboardingData();

        return Inertia::render('onboarding', [
            'ip' => $ip,
            'sshPublicKey' => $sshPublicKey,
            'currentStep' => $onboardingData['step'] ?? 'admin_user',
        ]);
    }

    public function store(Request $request)
    {
        $data = [];

        if ($request->step === 'admin_user') {
            $data = $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:255',
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->username' => $data['username'],
                'value->password' => $data['password'],
                'value->step' => 'dns',
            ]);
        } elseif ($request->step === 'dns') {
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
                'value->step' => 'ssh_key',
            ]);
        } elseif ($request->step === 'ssh_key') {
            $data = $request->validate([
                'app_name' => 'required|string|max:255',
                'repo_url' => 'required|string|max:255',
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->app_name' => $data['app_name'],
                'value->repo_url' => $data['repo_url'],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
            ], [
                'value->step' => 'setup',
            ]);
        }

        return back();
    }
}
