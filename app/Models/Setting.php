<?php

namespace App\Models;

use App\Enums\SettingsEnum;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $casts = [
        'key' => SettingsEnum::class,
    ];
}
