<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InformationGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_code',
        'group_date',
    ];

    protected $casts = [
        'group_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function bookingInformations()
    {
        return $this->hasMany(BookingInformation::class);
    }
}
