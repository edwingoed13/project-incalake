<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPickupDetail extends Model
{
    protected $fillable = [
        'booking_id',
        'pickup_type',
        'hotel_name',
        'hotel_address',
        'hotel_place_id',
        'hotel_lat',
        'hotel_lng',
        'distance_from_center',
        'extra_pickup_cost',
        'final_choice',
        'requires_logistics_approval',
        'approval_status',
        'logistics_notes',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'hotel_lat' => 'decimal:8',
        'hotel_lng' => 'decimal:8',
        'distance_from_center' => 'decimal:2',
        'extra_pickup_cost' => 'decimal:2',
        'requires_logistics_approval' => 'boolean',
        'approved_at' => 'datetime'
    ];

    /**
     * Get the booking that owns the pickup detail.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who approved the pickup.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate if hotel is within free pickup radius
     */
    public function isWithinFreeRadius($tourRadiusKm): bool
    {
        return $this->distance_from_center <= $tourRadiusKm;
    }

    /**
     * Calculate extra pickup cost based on distance
     */
    public function calculateExtraCost($tourRadiusKm, $costPerKm = 5): float
    {
        if ($this->isWithinFreeRadius($tourRadiusKm)) {
            return 0;
        }

        $extraDistance = $this->distance_from_center - $tourRadiusKm;
        $cost = $extraDistance * $costPerKm;

        // Apply minimum and maximum limits
        $minCost = 15;
        $maxCost = 50;

        return max($minCost, min($maxCost, $cost));
    }

    /**
     * Check if pickup needs logistics approval
     */
    public function needsLogisticsApproval(): bool
    {
        return $this->distance_from_center > 5; // More than 5km needs approval
    }
}
