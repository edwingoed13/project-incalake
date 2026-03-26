<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'value',
        'type',
        'start_date',
        'end_date',
        'color',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'type' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessors
    public function getIsPercentageAttribute()
    {
        return $this->type === 0;
    }

    public function getIsAmountAttribute()
    {
        return $this->type === 1;
    }
}
