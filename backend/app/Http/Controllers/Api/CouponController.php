<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Validate a coupon code
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado'
            ], 404);
        }

        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupón ya no está disponible o ha expirado'
            ], 400);
        }

        if (!$coupon->canBeApplied($request->amount)) {
            $minPurchase = $coupon->min_purchase;
            return response()->json([
                'success' => false,
                'message' => $minPurchase
                    ? "La compra mínima para este cupón es de $" . number_format($minPurchase, 2)
                    : 'Este cupón no puede ser aplicado a esta compra'
            ], 400);
        }

        $discount = $coupon->calculateDiscount($request->amount);

        return response()->json([
            'success' => true,
            'message' => 'Cupón aplicado correctamente',
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount_amount' => $discount,
                'description' => $coupon->description,
            ]
        ]);
    }
}
