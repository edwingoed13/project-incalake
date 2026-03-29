<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories_new';

    protected $fillable = [
        'code',
        'active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class, 'category_id');
    }

    public function translation($languageId = 1)
    {
        return $this->hasOne(CategoryTranslation::class, 'category_id')
            ->where('language_id', $languageId);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function categoryCode()
    {
        return $this->belongsTo(CategoryCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'tour_categories', 'category_id', 'tour_id');
    }

    // Accessor for name from translation (Spanish by default)
    public function getNameAttribute()
    {
        if (isset($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        $translation = $this->translations()->where('language_id', 1)->first();
        return $translation ? $translation->name : $this->code;
    }

    // Accessor for description from translation
    public function getDescriptionAttribute()
    {
        if (isset($this->attributes['description'])) {
            return $this->attributes['description'];
        }

        $translation = $this->translations()->where('language_id', 1)->first();
        return $translation ? $translation->description : null;
    }

    // Accessor for slug from translation
    public function getSlugAttribute()
    {
        if (isset($this->attributes['slug'])) {
            return $this->attributes['slug'];
        }

        $translation = $this->translations()->where('language_id', 1)->first();
        return $translation ? $translation->slug : $this->code;
    }

    // Accessor for tours count
    public function getToursCountAttribute()
    {
        return $this->tours()->count();
    }
}
