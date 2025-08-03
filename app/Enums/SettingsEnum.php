<?php

namespace App\Enums;

enum SettingsEnum: string
{
    case ADMIN_DOMAIN = 'admin_domain';
    case SITE_DOMAIN = 'site_domain';
    case FIRST_APP_NAME = 'first_app_name';
    case FIRST_REPO_URL = 'first_repo_url';

    case CURRENT_ONBOARDING_STEP = 'current_onboarding_step';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_DOMAIN => 'Admin domain',
            self::SITE_DOMAIN => 'Site domain',
            self::FIRST_APP_NAME => 'First app name',
            self::FIRST_REPO_URL => 'First repo URL',
            self::CURRENT_ONBOARDING_STEP => 'Current onboarding step',
        };
    }
}
