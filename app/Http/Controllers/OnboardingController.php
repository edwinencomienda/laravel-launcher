<?php

namespace App\Http\Controllers;

use App\Enums\SettingsEnum;
use App\Models\Setting;
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'step' => 'required|string|in:dns',
            'admin_domain' => 'required|string|max:255',
            'site_domain' => 'required|string|max:255',
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

        return back();
    }
}
