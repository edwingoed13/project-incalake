<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'phone',
        'leader_name',
        'booking_id',
        'information_group_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function informationGroup()
    {
        return $this->belongsTo(InformationGroup::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'booking_detail_payment');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'booking_detail_service');
    }
}
