<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\Offer;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

class PriceCalculatorService
{
    public function calculateTourPrice(
        Tour $tour,
        array $participants,
        ?string $couponCode = null,
        ?string $offerDate = null
    ): array {
        $subtotal = 0;
        $discounts = 0;
        $details = [];

        foreach ($participants as $participant) {
            $ageStageId = $participant['age_stage_id'];
            $quantity = $participant['quantity'];

            $unitPrice = $tour->prices()
                ->where('age_stage_id', $ageStageId)
                ->where('active', true)
                ->where('min_quantity', '<=', $quantity)
                ->where(function($query) use ($quantity) {
                    $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
                })
                ->first();

            if (!$unitPrice) {
                $unitPrice = $tour->prices()
                    ->where('age_stage_id', $ageStageId)
                    ->where('active', true)
                    ->orderBy('min_quantity')
                    ->first();
            }

            if ($unitPrice) {
                $price = $unitPrice->amount * $quantity;
                $subtotal += $price;

                $details[] = [
                    'age_stage_id' => $ageStageId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice->amount,
                    'subtotal' => $price,
                ];
            }
        }

        $appliedOffers = [];
        $appliedCoupon = null;

        if ($offerDate) {
            $activeOffers = Offer::where('product_id', $tour->id)
                ->where('start_date', '<=', $offerDate)
                ->where('end_date', '>=', $offerDate)
                ->get();

            foreach ($activeOffers as $offer) {
                $offerDiscount = $offer->type === 'percentage'
                    ? ($subtotal * $offer->value / 100)
                    : $offer->value;

                $discounts += $offerDiscount;
                $appliedOffers[] = [
                    'offer_id' => $offer->id,
                    'description' => $offer->description,
                    'discount' => $offerDiscount,
                ];
            }
        }

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where(function($query) {
                    $query->whereNull('product_id')->orWhere('product_id', request()->product_id);
                })
                ->first();

            if ($coupon) {
                $couponDiscount = $coupon->discount_type === 'percentage'
                    ? (($subtotal - $discounts) * $coupon->discount / 100)
                    : $coupon->discount;

                $discounts += $couponDiscount;
                $appliedCoupon = [
                    'coupon_id' => $coupon->id,
                    'code' => $coupon->code,
                    'description' => $coupon->description,
                    'discount' => $couponDiscount,
                ];
            }
        }

        $total = max(0, $subtotal - $discounts);

        return [
            'subtotal' => round($subtotal, 2),
            'discounts' => round($discounts, 2),
            'total' => round($total, 2),
            'details' => $details,
            'applied_offers' => $appliedOffers,
            'applied_coupon' => $appliedCoupon,
        ];
    }

    public function validateCoupon(string $code, ?int $productId = null): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return [
                'valid' => false,
                'message' => 'Cupón no encontrado',
            ];
        }

        if ($productId && $coupon->product_id && $coupon->product_id != $productId) {
            return [
                'valid' => false,
                'message' => 'Este cupón no es válido para este producto',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Cupón válido',
            'discount' => $coupon->discount,
            'discount_type' => $coupon->discount_type,
            'description' => $coupon->description,
        ];
    }

    public function getActiveOffers(int $productId, string $date): array
    {
        return Offer::where('product_id', $productId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->get()
            ->toArray();
    }
}