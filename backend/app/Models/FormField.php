<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'field_name',
        'field_name_attr',
        'field_type',
        'field_placeholder',
        'field_value',
        'field_values',
        'field_priority',
        'field_category_id',
    ];

    protected $casts = [
        'field_name' => 'array',
        'field_placeholder' => 'array',
        'field_values' => 'array',
        'field_priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function fieldCategory()
    {
        return $this->belongsTo(FieldCategory::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_form_field');
    }

    public function bookingInformations()
    {
        return $this->hasMany(BookingInformation::class);
    }
}
