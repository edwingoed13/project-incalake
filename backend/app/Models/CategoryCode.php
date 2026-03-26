<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'image_id',
    ];

    protected $casts = [
        'image_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
