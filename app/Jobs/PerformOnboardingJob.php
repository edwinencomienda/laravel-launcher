<?php

namespace App\Jobs;

use App\Actions\CreateNginxSiteAction;
use App\Actions\GenerateDomainSslAction;
use App\Actions\GetGithubRepoListAction;
use App\Enums\SettingsEnum;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Throwable;

class PerformOnboardingJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateStatus('processing');

        // check if you're in the server
        if (! isInServer()) {
            throw new \Exception('You are not in the server');
        }

        $onboardingData = json_decode(Setting::getByKey(SettingsEnum::CURRENT_ONBOARDING_DATA), true);
        $adminDomain = Setting::getByKey(SettingsEnum::ADMIN_DOMAIN);
        $siteDomain = Setting::getByKey(SettingsEnum::SITE_DOMAIN);

        DB::transaction(function () use ($onboardingData, $siteDomain, $adminDomain) {
            // step 1: create user
            $this->updateOnboardingStatusMessage('Creating user');
            User::create([
                'name' => $onboardingData['name'],
                'email' => $onboardingData['email'],
                'password' => $onboardingData['password'], // this was already hashed during the onboarding process
            ]);

            // step 2: clone repository
            $this->updateOnboardingStatusMessage('Cloning repository');
            $repo = (new GetGithubRepoListAction)->handle()->firstWhere('full_name', $onboardingData['repo_name']);
            $repoBranch = $onboardingData['repo_branch'] ?? $repo['default_branch'];
            $githubAccessToken = getGithubAccessToken();
            $bash = <<<BASH
            cd /home/raptor
            git clone -b {$repoBranch} https://x-access-token:{$githubAccessToken}@github.com/{$repo['full_name']} /home/raptor/{$siteDomain}
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
            Process::timeout(600)->run($bash)->throw();

            // step 2.1: build assets if found.
            $packageJsonPath = "/home/raptor/{$siteDomain}/package.json";
            $packageJson = file_exists($packageJsonPath) ? file_get_contents($packageJsonPath) : null;
            if ($packageJson) {
                $packageJson = json_decode($packageJson, true);
                if (isset($packageJson['scripts']['build'])) {
                    $this->updateOnboardingStatusMessage('Building site assets');
                    Process::timeout(600)->run("cd /home/raptor/{$siteDomain} && npm install && npm run build", function ($type, $output) {
                        echo $output;
                    })->throw();
                }
            }

            // step 3: create nginx site
            $this->updateOnboardingStatusMessage('Creating nginx site');
            $nginxSite = new CreateNginxSiteAction;
            $nginxSite->handle(
                rootPath: "/home/raptor/{$siteDomain}/public",
                domain: $siteDomain,
            );
            // this is the admin panel to manage the sites and deployments
            $this->updateOnboardingStatusMessage('Creating admin site');
            $nginxSite = new CreateNginxSiteAction;
            $nginxSite->handle(
                rootPath: '/home/raptor/raptor/public',
                domain: $adminDomain,
            );

            // step 4: generate domain ssl
            $this->updateOnboardingStatusMessage('Generating domain ssl');
            $ssl = new GenerateDomainSslAction;
            $ssl->handle(
                domain: $siteDomain,
            );
            $ssl->handle(
                domain: $adminDomain,
            );

            // reload nginx
            $this->updateOnboardingStatusMessage('Reloading nginx');
            Process::run('sudo nginx -t && sudo nginx -s reload')->throw();
        });

        $this->updateStatus('completed');
    }

    private function updateOnboardingStatusMessage(string $message): void
    {
        Setting::updateOrCreate([
            'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
        ], [
            'value->setup_status_message' => $message,
        ]);
    }

    private function updateStatus(string $status): void
    {
        Setting::updateOrCreate([
            'key' => SettingsEnum::CURRENT_ONBOARDING_DATA,
        ], [
            'value->status' => $status,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->updateStatus('failed');
        $this->updateOnboardingStatusMessage('Onboarding failed: '.$exception->getMessage());
    }
}
