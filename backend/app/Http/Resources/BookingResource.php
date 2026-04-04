<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,

            // Tour info (flat for frontend)
            'tour_id' => $this->tour_id,
            'tour_title' => $this->tour_title,
            'tour_slug' => $this->tour?->slug,
            'tour_date' => $this->tour_date,
            'tour_time' => $this->tour_time,

            // Customer info (flat)
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_country' => $this->customer_country,
            'customer_notes' => $this->customer_notes,

            // Participants (flat)
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'total_participants' => $this->total_participants,

            // Pricing (flat)
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,

            // Payment (flat)
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_id' => $this->payment_id,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),

            'status' => $this->status,
            'pickup_location' => $this->pickup_location,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Nested structures for compatibility with CheckoutPage
            'tour' => [
                'id' => $this->tour_id,
                'title' => $this->tour_title,
                'slug' => $this->tour?->slug,
            ],
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
                'country' => $this->customer_country,
            ],
            'pricing' => [
                'currency' => $this->currency,
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount,
                'tax_percentage' => (float) ($this->tax_percentage ?? 0),
                'tax_amount' => (float) ($this->tax_amount ?? 0),
                'total' => (float) $this->total,
            ],
            'participants' => [
                'adults' => $this->adults,
                'children' => $this->children,
                'infants' => $this->infants,
                'total' => $this->total_participants,
            ],
        ];
    }
}
