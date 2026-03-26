<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AITranslationSetting extends Model
{
    protected $table = 'ai_translation_settings';

    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'custom_prompt',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];
}
