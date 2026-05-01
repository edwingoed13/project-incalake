<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'translations',
        'active',
    ];

    protected $casts = [
        'translations' => 'array',
        'active' => 'boolean',
    ];

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_tag');
    }

    /**
     * Resolve the display name for the given language code, falling back to ES,
     * then to any non-empty translation, finally to the slug.
     */
    public function nameFor(string $language = 'ES'): string
    {
        $translations = $this->translations ?? [];
        $code = strtoupper($language);

        if (!empty($translations[$code])) {
            return $translations[$code];
        }
        if (!empty($translations['ES'])) {
            return $translations['ES'];
        }
        foreach ($translations as $name) {
            if (!empty($name)) {
                return $name;
            }
        }
        return $this->slug;
    }

    public static function generateUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'tag';
        $candidate = $slug;
        $i = 2;
        while (static::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $slug . '-' . $i;
            $i++;
        }
        return $candidate;
    }
}
