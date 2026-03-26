<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'information_value',
        'form_field_id',
        'information_group_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function formField()
    {
        return $this->belongsTo(FormField::class);
    }

    public function informationGroup()
    {
        return $this->belongsTo(InformationGroup::class);
    }
}
