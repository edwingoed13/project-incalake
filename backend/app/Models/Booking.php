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
                // Token expira en 7 días (balance entre seguridad y usabilidad)
                $booking->confirmation_token_expires_at = now()->addDays(7);
            }

            // Extract security token from booking_code if it was generated
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
    public static function generateBookingCode()
    {
        $year = date('Y');
        $lastBooking = self::whereYear('created_at', $year)->latest('id')->first();

        // Extract number from existing code format (BK-YYYY-NNNN or BK-YYYY-NNNN-TTTTTTTT)
        if ($lastBooking) {
            $parts = explode('-', $lastBooking->booking_code);
            $number = isset($parts[2]) ? (int) $parts[2] + 1 : 1;
        } else {
            $number = 1;
        }

        // Generate 8-character security token (4 bytes hex)
        $securityToken = bin2hex(random_bytes(4));

        // Format: BK-2026-0123-a8f3e9d4
        return sprintf('BK-%s-%04d-%s', $year, $number, $securityToken);
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
}
