<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'age_stage_id',
        'nationality_id',
        'min_age',
        'max_age',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'max_age' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ageStage()
    {
        return $this->belongsTo(AgeStage::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
}
