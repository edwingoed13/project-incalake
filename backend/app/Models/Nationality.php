<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nationality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'code',
        'order',
        'editable',
        'translations',
    ];

    protected $casts = [
        'order' => 'integer',
        'editable' => 'boolean',
        'translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function priceDetails()
    {
        return $this->hasMany(PriceDetail::class);
    }
}
