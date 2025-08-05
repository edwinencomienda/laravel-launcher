<?php

namespace App\Actions;

use App\Enums\SettingsEnum;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

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

        DB::transaction(function () use ($onboardingData, $siteDomain, $adminDomain) {
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
            php artisan key:generate

            # set the db connection to mysql
            sed -i "s/^#\?DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
            sed -i "s/^# *DB_DATABASE=.*/DB_DATABASE=$(cat /home/raptor/.raptor/db_database)/" .env
            sed -i "s/^# *DB_USERNAME=.*/DB_USERNAME=$(cat /home/raptor/.raptor/db_username)/" .env
            sed -i "s/^# *DB_PASSWORD=.*/DB_PASSWORD=$(cat /home/raptor/.raptor/db_password)/" .env


            php artisan config:cache
            php artisan migrate
            BASH;
            Process::run($bash)->throw();

            // step 3: create nginx site
            $this->updateOnboardingStatus('Creating nginx site');
            $nginxSite = new CreateNginxSiteAction;
            $nginxSite->handle(
                rootPath: "/home/raptor/{$siteDomain}/public",
                domain: $siteDomain,
            );
            // this is the admin panel to manage the sites and deployments
            $this->updateOnboardingStatus('Creating admin site');
            $nginxSite = new CreateNginxSiteAction;
            $nginxSite->handle(
                rootPath: '/home/raptor/raptor/public',
                domain: $adminDomain,
            );

            // step 4: generate domain ssl
            $this->updateOnboardingStatus('Generating domain ssl');
            $domains = [$siteDomain, $adminDomain];
            $ssl = new GenerateDomainSslAction;
            $ssl->handle($domains);
        });
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
