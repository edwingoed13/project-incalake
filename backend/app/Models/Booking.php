<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'security_token',
        'confirmation_token',
        'confirmation_token_expires_at',
        'tour_id',
        'tour_title',
        'tour_date',
        'tour_time',
        'customer_name',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'customer_country',
        'customer_notes',
        'adults',
        'children',
        'infants',
        'total_participants',
        'currency',
        'subtotal',
        'discount',
        'tax_percentage',
        'tax_amount',
        'total',
        'payment_method',
        'payment_status',
        'payment_id',
        'payment_data',
        'paid_at',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'pickup_location',
        'pickup_time',
        'participants_data',
        'admin_notes',
    ];

    protected $casts = [
        'tour_date' => 'date',
        // 'tour_time' => 'datetime', // Commented - tour_time is a time string
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_data' => 'array',
        'participants_data' => 'array',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'confirmation_token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Boot method for auto-generating tokens
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // Auto-generate confirmation token if not set
            if (empty($booking->confirmation_token)) {
                $booking->confirmation_token = self::generateConfirmationToken();
                // 30 días: los clientes reservan con semanas de anticipación y
                // completan sus datos después — 7 días dejaba links del email
                // muertos antes del tour.
                $booking->confirmation_token_expires_at = now()->addDays(30);
            }

            // Legacy column. The tail of the code used to be treated as a
            // security token; nothing ever verified it, and the format no
            // longer produces one. Kept in step for the rows that predate the
            // change rather than left to drift.
            if (!empty($booking->booking_code) && strpos($booking->booking_code, '-') !== false) {
                $parts = explode('-', $booking->booking_code);
                if (count($parts) === 4) {
                    $booking->security_token = $parts[3];
                }
            }
        });
    }

    // Relationships
    /**
     * Get the pickup details for the booking.
     */
    public function pickupDetail()
    {
        return $this->hasOne(BookingPickupDetail::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function travelers()
    {
        return $this->hasMany(BookingTraveler::class)->orderBy('order');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('tour_date', '>=', now()->toDateString())
                    ->where('status', '!=', 'cancelled');
    }

    // Accessors
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }

    public function getPaymentStatusBadgeAttribute()
    {
        return match($this->payment_status) {
            'pending' => '<span class="badge bg-warning">Pendiente</span>',
            'paid' => '<span class="badge bg-success">Pagado</span>',
            'failed' => '<span class="badge bg-danger">Fallido</span>',
            'refunded' => '<span class="badge bg-info">Reembolsado</span>',
            default => '<span class="badge bg-secondary">Desconocido</span>',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">Pendiente</span>',
            'confirmed' => '<span class="badge bg-success">Confirmado</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelado</span>',
            'completed' => '<span class="badge bg-primary">Completado</span>',
            default => '<span class="badge bg-secondary">Desconocido</span>',
        };
    }

    // Helper methods
    /**
     * BK-26-0822-1234 — year, month+day, and four random digits.
     *
     * Replaces BK-2026-0174-c2b9408b, which nobody could read over the phone.
     * The old eight-hex tail was introduced as a security token, but nothing
     * ever checked it: access to a booking is gated on the customer's email
     * (see BookingController::show, which 403s without a match), so the tail
     * was buying length rather than protection.
     *
     * The tail still has a job — it is what keeps two bookings on the same day
     * apart, now that the running number is gone. Four digits is 10,000 codes
     * per day, and on a day with 30 bookings roughly a 4% chance that two land
     * on the same one, which is far too likely to leave to chance against a
     * unique index. So it asks the database rather than trusting the odds.
     */
    public static function generateBookingCode()
    {
        $prefix = sprintf('BK-%s-%s', date('y'), date('md'));

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $prefix . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (!self::where('booking_code', $code)->exists()) {
                return $code;
            }
        }

        // Twenty collisions means the day is busy enough that four digits no
        // longer fit. Widen the tail rather than hand back a code the unique
        // index will reject on insert.
        return $prefix . '-' . bin2hex(random_bytes(3));
    }

    public static function generateConfirmationToken()
    {
        return \Illuminate\Support\Str::random(64);
    }

    public function markAsPaid($paymentId, $paymentData = [])
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_id' => $paymentId,
            'payment_data' => $paymentData,
            'paid_at' => now(),
            'status' => 'confirmed',
            // The confirmation email (with the tokened link) goes out at payment
            // time — restart the 30-day window so the link outlives late
            // traveler-data updates.
            'confirmation_token_expires_at' => now()->addDays(30),
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Confirm the booking. Also used to re-activate a previously cancelled
     * booking (clears the cancellation fields).
     */
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ]);
    }
}
