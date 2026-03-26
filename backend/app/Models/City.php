<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'timezone',
        'latitude',
        'longitude',
        'active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'active' => 'boolean',
    ];

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function activeTours(): HasMany
    {
        return $this->tours()->where('active', true)->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name}, {$this->country_code}";
    }
}
