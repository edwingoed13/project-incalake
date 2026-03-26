<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_gift',
        'user_id',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'price' => 'array',
        'is_gift' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function galleries()
    {
        return $this->belongsToMany(Gallery::class, 'resource_gallery');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_resource');
    }
}
