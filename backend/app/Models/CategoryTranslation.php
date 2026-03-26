<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'language_id',
        'name',
        'description',
        'slug',
    ];

    /**
     * Relación: Una traducción pertenece a una categoría
     */
    public function category()
    {
        return $this->belongsTo(CategoryNew::class, 'category_id');
    }

    /**
     * Relación: Una traducción pertenece a un idioma
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    /**
     * Evento: Generar slug automáticamente antes de guardar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($translation) {
            if (empty($translation->slug) && !empty($translation->name)) {
                $translation->slug = Str::slug($translation->name);
            }
        });
    }
}
