<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Price extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quantity',
        'amount',
        'price_detail_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function priceDetail()
    {
        return $this->belongsTo(PriceDetail::class);
    }
}
