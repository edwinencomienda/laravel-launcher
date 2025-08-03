<?php

namespace App\Enums;

enum SettingsEnum: string
{
    case ADMIN_DOMAIN = 'admin_domain';
    case SITE_DOMAIN = 'site_domain';
    case CURRENT_ONBOARDING_DATA = 'current_onboarding_data';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_DOMAIN => 'Admin domain',
            self::SITE_DOMAIN => 'Site domain',
            self::CURRENT_ONBOARDING_DATA => 'Current onboarding data',
        };
    }
}
