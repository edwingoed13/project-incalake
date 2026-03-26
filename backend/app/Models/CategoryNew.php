<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CategoryNew extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories_new';

    protected $fillable = [
        'code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Relación: Una categoría tiene muchas traducciones
     */
    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class, 'category_id');
    }

    /**
     * Obtener traducción por idioma
     */
    public function translation($languageCode = 'es')
    {
        return $this->translations()
            ->whereHas('language', function($query) use ($languageCode) {
                $query->where('code', $languageCode);
            })
            ->first();
    }

    /**
     * Scope: Categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Con traducciones cargadas
     */
    public function scopeWithTranslations($query, $languageCode = null)
    {
        $query->with(['translations' => function($q) use ($languageCode) {
            if ($languageCode) {
                $q->whereHas('language', function($query) use ($languageCode) {
                    $query->where('code', $languageCode);
                });
            }
            $q->with('language');
        }]);

        return $query;
    }

    /**
     * Obtener nombre traducido (helper)
     */
    public function getName($languageCode = 'es')
    {
        $translation = $this->translation($languageCode);
        return $translation ? $translation->name : $this->code;
    }

    /**
     * Obtener descripción traducida (helper)
     */
    public function getDescription($languageCode = 'es')
    {
        $translation = $this->translation($languageCode);
        return $translation ? $translation->description : '';
    }

    /**
     * Evento: Generar código automáticamente antes de crear
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = Str::slug($category->name ?? 'category-' . time());
            }
        });
    }
}
