<?php

namespace App\Enums;

enum SettingsEnum: string
{
    case ADMIN_DOMAIN = 'admin_domain';

    case SITE_DOMAIN = 'site_domain';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_DOMAIN => 'Admin domain',
            self::SITE_DOMAIN => 'Site domain',
        };
    }
}
