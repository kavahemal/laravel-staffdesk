<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** App-wide settings edited by Admin; read through SettingService (cached). */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
