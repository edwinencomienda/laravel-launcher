<?php

namespace App\Http\Controllers;

use App\Enums\SettingsEnum;
use App\Models\Setting;
use App\Rules\FqdnRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        // get ip address
        $ip = exec('hostname -I | awk \'{print $1}\'') ?: '127.0.0.1';
        $sshPublicKey = file_exists('/home/raptor/.ssh/id_rsa.pub')
            ? file_get_contents('/home/raptor/.ssh/id_rsa.pub')
            : null;

        return Inertia::render('onboarding', [
            'ip' => $ip,
            'sshPublicKey' => $sshPublicKey,
            'currentStep' => Setting::getByKey(SettingsEnum::CURRENT_ONBOARDING_STEP) ?: 'dns',
        ]);
    }

    public function store(Request $request)
    {
        $data = [];

        if ($request->step === 'dns') {
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
                'key' => SettingsEnum::CURRENT_ONBOARDING_STEP,
            ], [
                'value' => 'ssh_key',
            ]);
        } elseif ($request->step === 'ssh_key') {
            $data = $request->validate([
                'app_name' => 'required|string|max:255',
                'repo_url' => 'required|string|max:255',
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::FIRST_APP_NAME,
            ], [
                'value' => $data['app_name'],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::FIRST_REPO_URL,
            ], [
                'value' => $data['repo_url'],
            ]);

            Setting::updateOrCreate([
                'key' => SettingsEnum::CURRENT_ONBOARDING_STEP,
            ], [
                'value' => 'setup',
            ]);
        }

        return back();
    }
}
