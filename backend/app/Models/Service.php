<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'url',
        'page_title',
        'page_description',
        'main_image',
        'show_slider',
        'thumbnail',
        'rating',
        'reviews',
        'language_id',
        'service_code_id',
        'location',
        'uri',
    ];

    protected $casts = [
        'show_slider' => 'boolean',
        'rating' => 'decimal:2',
        'reviews' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function serviceCode()
    {
        return $this->belongsTo(ServiceCode::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function bookingDetails()
    {
        return $this->belongsToMany(BookingDetail::class, 'booking_detail_service');
    }
}
