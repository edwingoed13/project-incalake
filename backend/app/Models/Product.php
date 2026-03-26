<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'code',
        'nearest_city',
        'nearest_airport',
        'service_id',
        'start_time',
        'duration',
        'capacity',
        'attachments',
        'product_code_id',
        'status',
        'policies',
        'booking_anticipation',
        'data_requirement',
        'multiple_forms',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'boolean',
        'data_requirement' => 'integer',
        'multiple_forms' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function productCode()
    {
        return $this->belongsTo(ProductCode::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function galleries()
    {
        return $this->belongsToMany(Gallery::class, 'product_gallery');
    }

    public function tab()
    {
        return $this->hasOne(Tab::class);
    }

    public function additionalTabs()
    {
        return $this->hasMany(AdditionalTab::class);
    }

    public function priceDetails()
    {
        return $this->hasMany(PriceDetail::class);
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    public function blockouts()
    {
        return $this->hasMany(Blockout::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'product_resource');
    }

    public function formFields()
    {
        return $this->belongsToMany(FormField::class, 'product_form_field');
    }

    public function serviceDetails()
    {
        return $this->hasMany(ServiceDetail::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
