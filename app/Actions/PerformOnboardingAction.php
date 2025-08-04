<?php

namespace App\Actions;

use App\Enums\SettingsEnum;
use App\Models\Setting;
use App\Models\User;
use App\Services\SshService;

class PerformOnboardingAction
{
    public function handle()
    {
        // check if you're in the server
        if (! isInServer()) {
            throw new \Exception('You are not in the server');
        }

        $onboardingData = json_decode(Setting::getByKey(SettingsEnum::CURRENT_ONBOARDING_DATA), true);
        $adminDomain = Setting::getByKey(SettingsEnum::ADMIN_DOMAIN);
        $siteDomain = Setting::getByKey(SettingsEnum::SITE_DOMAIN);

        // step 1: create user
        $this->updateOnboardingStatus('Creating user');
        $user = User::create([
            'name' => $onboardingData['name'],
            'email' => $onboardingData['email'],
            'password' => $onboardingData['password'],
        ]);

        // step 2: clone repository
        $this->updateOnboardingStatus('Cloning repository');
        $repoUrl = convertGithubUrlToSshUrl($onboardingData['repo_url']);
        $bash = <<<BASH
        cd /home/raptor
        git clone {$repoUrl} /home/raptor/{$siteDomain}
        cd /home/raptor/{$siteDomain}

        composer install --no-dev
        cp .env.example .env
        php artisan config:cache
        php artisan key:generate
        php artisan migrate
        BASH;

        $ssh = new SshService(
            host: '5.223.75.35',
            user: 'raptor',
        );

        $ssh->connect();

        $output = $ssh->runCommand($bash);
    }

    private function updateOnboardingStatus(string $message): void
    {
        Setting::updateOrCreate([
            'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
        ], [
            'value->setup_status_message' => $message,
        ]);
    }
}
