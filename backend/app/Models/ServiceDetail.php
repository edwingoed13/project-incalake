<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_date',
        'quantity',
        'total_price',
        'discount',
        'product_id',
    ];

    protected $casts = [
        'service_date' => 'datetime',
        'quantity' => 'integer',
        'total_price' => 'decimal:3',
        'discount' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
