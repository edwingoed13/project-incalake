<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\BookingConfirmationEmail;
use App\Mail\GroupBookingConfirmationEmail;
use App\Services\GoogleCalendarService;

class BookingController extends Controller
{
    /**
     * Create a new booking (Step 1: Create pending booking)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Debug: Log all incoming data
        \Log::info('Booking request received', [
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'tour_date' => 'required|date|after_or_equal:today',
            'tour_time' => 'nullable|string',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_first_name' => 'required_without:customer_name|nullable|string|max:100',
            'customer_last_name' => 'required_without:customer_name|nullable|string|max:100',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_country' => 'nullable|string|size:2',
            'customer_notes' => 'nullable|string',
            'pickup_location' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:culqi,paypal',
        ]);

        if ($validator->fails()) {
            $errorDetails = [
                'errors' => $validator->errors()->toArray(),
                'data' => $request->all()
            ];

            \Log::error('Booking validation failed', $errorDetails);

            // Return detailed error for debugging
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'debug_data' => $request->all() // Added for debugging
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Default payment_method to 'culqi' if not provided (selected in step 2)
            $paymentMethod = $request->payment_method ?? 'culqi';

            // Get tour information
            $tour = Tour::findOrFail($request->tour_id);

            // Get tour title from translations
            $tourTranslation = DB::table('tour_translations')
                ->where('tour_id', $tour->id)
                ->where('language_id', 1) // Spanish
                ->first();

            $tourTitle = $tourTranslation ? $tourTranslation->h1_title : 'Tour';

            // Validate payment method is allowed for this tour
            if ($tour->payment_method !== 'all' && $tour->payment_method !== $paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Método de pago no permitido para este tour'
                ], 400);
            }

            // Calculate participants
            $adults = $request->adults ?? 1;
            $children = $request->children ?? 0;
            $infants = $request->infants ?? 0;
            $totalParticipants = $adults + $children + $infants;

            // Get price for adults (age_stage_id = 2)
            // First try exact range match (e.g. qty=3 for row min=3 max=3)
            $adultPrice = DB::table('tour_prices')
                ->where('tour_id', $tour->id)
                ->where('age_stage_id', 2) // Adults
                ->where('min_quantity', '<=', $adults)
                ->where('max_quantity', '>=', $adults)
                ->where('active', 1)
                ->first();

            // If no exact match (qty exceeds all defined ranges), use the last range (highest min_quantity)
            // e.g. table has 1-1, 2-2, 3-3 and booking is 5 adults -> use 3-3 price
            if (!$adultPrice) {
                $adultPrice = DB::table('tour_prices')
                    ->where('tour_id', $tour->id)
                    ->where('age_stage_id', 2)
                    ->where('active', 1)
                    ->orderBy('min_quantity', 'desc')
                    ->first();
            }

            // Use price or default to 50 USD per adult
            $pricePerAdult = $adultPrice ? $adultPrice->amount : 50.00;

            \Log::info('Booking price calculation', [
                'tour_id' => $tour->id,
                'adults' => $adults,
                'children' => $children,
                'price_per_adult' => $pricePerAdult,
                'adult_price_found' => $adultPrice ? true : false
            ]);

            // Check if there's an offer discount from frontend
            $discount = 0;
            $originalSubtotal = ($adults * $pricePerAdult) + ($children * $pricePerAdult * 0.5) + ($infants * 0);

            // Use frontend-provided pricing if available (includes offer calculations)
            if ($request->has('total_amount') && (float) $request->total_amount > 0) {
                $total = (float) $request->total_amount;
                $subtotal = $total;

                // Calculate discount if offer is active
                if ($request->has('has_offer') && $request->has_offer) {
                    if ($request->has('original_price')) {
                        $originalPrice = (float) $request->original_price;
                        $basePrice = (float) $request->base_price;
                        $discount = ($originalPrice - $basePrice) * $adults;
                        $subtotal = $originalPrice * $adults;
                    }
                }
            } else {
                // Fallback to calculated prices
                $subtotal = $originalSubtotal;
                $total = $subtotal - $discount;
            }

            // Also check for old field name for backward compatibility
            if (!isset($total) || $total < 1.00) {
                if ($request->has('total_price') && (float) $request->total_price > 0) {
                    $total = (float) $request->total_price;
                    $subtotal = $total;
                }
            }

            // Calculate tax
            $taxPercentage = $tour->tax_percentage ?? 0;
            $taxAmount = $taxPercentage > 0 ? round($total * $taxPercentage / 100, 2) : 0;
            $totalWithTax = $total + $taxAmount;

            // Ensure minimum amount for Culqi (requires at least $1.00 = 100 cents)
            if ($totalWithTax < 1.00) {
                $totalWithTax = 1.00;
                $subtotal = 1.00;
                $taxAmount = 0;
            }

            // Generate unique booking code
            $bookingCode = Booking::generateBookingCode();

            // Resolve customer name: prefer first_name + last_name, fallback to customer_name
            $firstName = trim((string) ($request->customer_first_name ?? ''));
            $lastName = trim((string) ($request->customer_last_name ?? ''));
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                $fullName = (string) $request->customer_name;
            }

            // Create booking
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'tour_id' => $tour->id,
                'tour_title' => $tourTitle,
                'tour_date' => $request->tour_date,
                'tour_time' => $request->tour_time,
                'customer_name' => $fullName,
                'customer_first_name' => $firstName ?: null,
                'customer_last_name' => $lastName ?: null,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_country' => $request->customer_country,
                'customer_notes' => $request->customer_notes,
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'total_participants' => $totalParticipants,
                'currency' => $tour->currency ?? 'USD',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'total' => $totalWithTax,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'status' => 'pending',
                'pickup_location' => $request->pickup_location,
            ]);

            DB::commit();

            // Load tour relation
            $booking->load('tour');

            return response()->json([
                'success' => true,
                'message' => 'Reserva creada exitosamente',
                'booking' => new BookingResource($booking),
                'payment_config' => [
                    'culqi_public_key' => config('services.culqi.public_key'),
                    'paypal_client_id' => config('services.paypal.client_id'),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment configuration (public keys, etc)
     *
     * @return IlluminateHttpJsonResponse
     */
    public function paymentConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'culqi_public_key' => config('services.culqi.public_key'),
                'paypal_client_id' => config('services.paypal.client_id'),
                'currencies' => ['USD', 'PEN'],
                'payment_methods' => ['culqi', 'paypal']
            ]
        ]);
    }
    /**
     * Confirm payment with Culqi (Step 2: Process Culqi payment)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmCulqiPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'payment_data' => 'nullable|array',
            // Multi-tour cart: sibling bookings paid in the same Culqi charge.
            'booking_ids' => 'nullable|array',
            'booking_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);

            // Group = primary booking + any sibling bookings from the same
            // multi-tour cart. A Culqi token is single-use, so we make ONE
            // charge for the SUM of the group and mark them all paid.
            $groupIds = collect([$booking->id])
                ->merge($request->input('booking_ids', []))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();
            $bookings = Booking::whereIn('id', $groupIds)->get();

            if ($bookings->isEmpty()) {
                $bookings = collect([$booking]);
            }

            if ($bookings->firstWhere('payment_status', 'paid')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más reservas de este grupo ya fueron pagadas'
                ], 400);
            }

            // Safety: the whole group must be the same customer + currency.
            if ($bookings->pluck('customer_email')->unique()->count() > 1
                || $bookings->pluck('currency')->unique()->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las reservas del grupo no son consistentes'
                ], 400);
            }

            // Integrate with Culqi API to create charge
            $culqiToken = $request->token;
            $secretKey = config('services.culqi.secret_key');

            // Single charge for the group. The customer can pay the full total
            // or the advance (deposit) when the tour enables it; 'advance' uses
            // each tour's advance_payment_percentage via calculateExpectedPaymentAmount().
            $paymentMode = $request->input('payment_mode', 'full');
            if ($paymentMode === 'advance') {
                $amountInCents = (int) round($bookings->sum(fn ($b) => $this->calculateExpectedPaymentAmount($b)) * 100);
            } else {
                $amountInCents = (int) round($bookings->sum('total') * 100);
            }

            // Prepare description (Culqi requires 5-80 characters)
            $description = "Reserva {$booking->booking_code}";
            if ($bookings->count() > 1) {
                $description .= " +" . ($bookings->count() - 1) . " tour(s)";
            } elseif ($booking->tour_title) {
                $description .= " - {$booking->tour_title}";
            }
            // Ensure description is between 5 and 80 characters
            $description = mb_substr($description, 0, 80);
            if (mb_strlen($description) < 5) {
                $description = "Tour Booking " . $booking->booking_code;
            }

            // Prepare Culqi charge data
            $chargeData = [
                'amount' => $amountInCents,
                'currency_code' => $booking->currency,
                'email' => $booking->customer_email,
                'source_id' => $culqiToken,
                'description' => $description,
                'metadata' => [
                    'booking_code' => $booking->booking_code,
                    'tour_title' => (string) substr($booking->tour_title ?? '', 0, 100),
                    'customer_name' => $booking->customer_name,
                ],
            ];

            // Add antifraud_details if we have name data (improves approval rate + shows in Culqi panel)
            $firstName = $booking->customer_first_name;
            $lastName = $booking->customer_last_name;
            if ($firstName || $lastName) {
                $chargeData['antifraud_details'] = array_filter([
                    'first_name' => $firstName ?: null,
                    'last_name' => $lastName ?: null,
                    'country_code' => $booking->customer_country ?: null,
                    'phone' => $booking->customer_phone ?: null,
                ]);
            }

            \Log::info('Culqi charge request', [
                'booking_id' => $booking->id,
                'amount_cents' => $amountInCents,
                'currency' => $booking->currency
            ]);

            // Make API call to Culqi
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.culqi.com/v2/charges');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chargeData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception("Error de conexión con Culqi: {$curlError}");
            }

            $chargeResponse = json_decode($response, true);

            \Log::info('Culqi charge response', [
                'http_code' => $httpCode,
                'response' => $chargeResponse,
                'booking_id' => $booking->id
            ]);

            // Check if charge was successful
            if ($httpCode !== 200 && $httpCode !== 201) {
                \Log::error('Culqi payment failed', [
                    'booking_id' => $booking->id,
                    'http_code' => $httpCode,
                    'error' => $chargeResponse
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $chargeResponse['merchant_message'] ?? $chargeResponse['user_message'] ?? 'Error al procesar el pago con Culqi',
                    'error_code' => $chargeResponse['error_code'] ?? 'unknown',
                    'error' => $chargeResponse
                ], 400);
            }

            // Extract charge ID from response
            $chargeId = $chargeResponse['id'] ?? null;

            if (!$chargeId) {
                throw new \Exception('No se recibió ID de cargo de Culqi');
            }

            // One successful charge covers the whole group. Mark every booking
            // paid against the same charge and add a SEPARATE Google Calendar
            // event per tour/date (per client request — calendar stays split).
            foreach ($bookings as $b) {
                $b->markAsPaid($chargeId, [
                    'token' => $culqiToken,
                    'gateway' => 'culqi',
                    'charge_data' => $chargeResponse,
                    'payment_data' => $request->payment_data,
                    'group_total_charged' => $amountInCents,
                    'group_booking_ids' => $bookings->pluck('id')->all(),
                ]);

                \Log::info('Booking marked as paid', [
                    'booking_id' => $b->id,
                    'charge_id' => $chargeId,
                    'group_size' => $bookings->count(),
                ]);

                // Admin Google Calendar event — one per tour (non-blocking).
                try {
                    $calendarService = new GoogleCalendarService();
                    $calendarService->createBookingEvent([
                        'booking_code'    => $b->booking_code,
                        'tour_title'      => $b->tour_title,
                        'tour_date'       => \Carbon\Carbon::parse($b->tour_date)->format('Y-m-d'),
                        'tour_time'       => \Carbon\Carbon::parse($b->tour_time)->format('H:i:s'),
                        'adults'          => $b->adults,
                        'children'        => $b->children,
                        'customer_name'   => $b->customer_name,
                        'customer_email'  => $b->customer_email,
                        'customer_phone'  => $b->customer_phone,
                        'total'           => $b->total,
                        'currency'        => $b->currency,
                        'payment_method'  => $b->payment_method ?? 'culqi',
                    ]);
                } catch (\Exception $calException) {
                    \Log::error('Failed to create Google Calendar event', [
                        'booking_id' => $b->id,
                        'error' => $calException->getMessage()
                    ]);
                }
            }

            // ONE confirmation email for the whole purchase (non-blocking).
            // Single tour -> existing per-booking template (unchanged).
            // Multi-tour -> one consolidated email listing every tour.
            try {
                $freshGroup = $bookings->map(fn ($b) => $b->fresh());
                // Culqi charges the FULL group total (no advance split).
                $paidNow = round($amountInCents / 100, 2);
                if ($freshGroup->count() === 1) {
                    $single = $freshGroup->first();
                    Mail::to($single->customer_email)->send(new BookingConfirmationEmail($single, false, $paidNow));
                    Mail::to('reservas@incalake.com')->send(new BookingConfirmationEmail($single, true, $paidNow));
                } else {
                    Mail::to($freshGroup->first()->customer_email)->send(new GroupBookingConfirmationEmail($freshGroup, false, $paidNow));
                    Mail::to('reservas@incalake.com')->send(new GroupBookingConfirmationEmail($freshGroup, true, $paidNow));
                }
                \Log::info('Confirmation email sent', [
                    'group_size' => $freshGroup->count(),
                    'booking_ids' => $freshGroup->pluck('id')->all(),
                ]);
            } catch (\Exception $mailException) {
                \Log::error('Failed to send confirmation email', [
                    'booking_ids' => $bookings->pluck('id')->all(),
                    'error' => $mailException->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'booking' => new BookingResource($booking->fresh()),
                'charge_id' => $chargeId,
                'group_booking_ids' => $bookings->pluck('id')->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm payment with PayPal (Step 2: Process PayPal payment)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Calculate the expected PayPal payment amount for a booking.
     * Respects the tour's advance_payment_percentage (CMS-configurable).
     * If not set, charges 100% of the booking total.
     */
    protected function calculateExpectedPaymentAmount(Booking $booking): float
    {
        $tour = $booking->tour;
        $percentage = 100;

        if ($tour && $tour->advance_payment_percentage) {
            $percentage = max(1, min(100, (int) $tour->advance_payment_percentage));
        }

        $total = (float) $booking->total;
        $amount = round($total * $percentage / 100, 2);

        return $amount;
    }

    public function confirmPayPalPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'payment_data' => 'nullable|array',
            // Multi-tour cart: sibling bookings captured in the same order.
            'booking_ids' => 'nullable|array',
            'booking_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);

            // Group = primary + sibling bookings from the same multi-tour cart.
            // A PayPal order can only be captured once, so one capture covers
            // the whole group.
            $groupIds = collect([$booking->id])
                ->merge($request->input('booking_ids', []))
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values();
            $bookings = Booking::whereIn('id', $groupIds)->get();

            if ($bookings->isEmpty()) {
                $bookings = collect([$booking]);
            }

            if ($bookings->firstWhere('payment_status', 'paid')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más reservas de este grupo ya fueron pagadas'
                ], 400);
            }

            if ($bookings->pluck('customer_email')->unique()->count() > 1
                || $bookings->pluck('currency')->unique()->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las reservas del grupo no son consistentes'
                ], 400);
            }

            $orderId = $request->order_id;
            $paypal = app(\App\Services\PayPalService::class);

            // 1. Capture the order via PayPal REST API v2
            $captureResponse = $paypal->captureOrder($orderId);
            $status = $paypal->getCaptureStatus($captureResponse);

            if ($status !== 'COMPLETED') {
                \Log::warning('PayPal capture not completed', [
                    'booking_id' => $booking->id,
                    'order_id' => $orderId,
                    'status' => $status
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Pago no completado. Estado: {$status}"
                ], 400);
            }

            // 2. Validate captured amount == SUM of the whole group's expected.
            // The customer may pay the full total or the advance (deposit).
            $capturedAmount = $paypal->getCapturedAmount($captureResponse);
            $paymentMode = $request->input('payment_mode', 'full');
            $expectedAmount = $paymentMode === 'advance'
                ? round($bookings->sum(fn ($b) => $this->calculateExpectedPaymentAmount($b)), 2)
                : round($bookings->sum('total'), 2);

            if ($capturedAmount === null || abs($capturedAmount - $expectedAmount) > 0.01) {
                \Log::error('PayPal amount mismatch', [
                    'booking_ids' => $bookings->pluck('id')->all(),
                    'order_id' => $orderId,
                    'expected' => $expectedAmount,
                    'captured' => $capturedAmount
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El monto pagado no coincide con la reserva'
                ], 400);
            }

            // 3. Get transaction ID for reconciliation
            $transactionId = $paypal->getTransactionId($captureResponse) ?? $orderId;

            // 4. One capture covers the whole group — mark every booking paid
            // and add a SEPARATE Google Calendar event per tour/date.
            foreach ($bookings as $b) {
                $b->markAsPaid($transactionId, [
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                    'gateway' => 'paypal',
                    'captured_amount' => $capturedAmount,
                    'payer' => $captureResponse['payer'] ?? null,
                    'capture_response' => $captureResponse,
                    'group_total_captured' => $capturedAmount,
                    'group_booking_ids' => $bookings->pluck('id')->all(),
                ]);

                try {
                    $calendarService = new GoogleCalendarService();
                    $calendarService->createBookingEvent([
                        'booking_code'    => $b->booking_code,
                        'tour_title'      => $b->tour_title,
                        'tour_date'       => \Carbon\Carbon::parse($b->tour_date)->format('Y-m-d'),
                        'tour_time'       => \Carbon\Carbon::parse($b->tour_time)->format('H:i:s'),
                        'adults'          => $b->adults,
                        'children'        => $b->children,
                        'customer_name'   => $b->customer_name,
                        'customer_email'  => $b->customer_email,
                        'customer_phone'  => $b->customer_phone,
                        'total'           => $b->total,
                        'currency'        => $b->currency,
                        'payment_method'  => 'paypal',
                    ]);
                } catch (\Exception $calException) {
                    \Log::error('Failed to create Google Calendar event (PayPal)', [
                        'booking_id' => $b->id,
                        'error' => $calException->getMessage()
                    ]);
                }
            }

            // ONE confirmation email for the whole purchase (non-blocking).
            // Single tour -> existing template; multi-tour -> consolidated.
            try {
                $freshGroup = $bookings->map(fn ($b) => $b->fresh());
                // PayPal captured the real amount (may be an advance if the
                // tour uses advance_payment_percentage < 100).
                $paidNow = round((float) $capturedAmount, 2);
                if ($freshGroup->count() === 1) {
                    $single = $freshGroup->first();
                    Mail::to($single->customer_email)->send(new BookingConfirmationEmail($single, false, $paidNow));
                    Mail::to('reservas@incalake.com')->send(new BookingConfirmationEmail($single, true, $paidNow));
                } else {
                    Mail::to($freshGroup->first()->customer_email)->send(new GroupBookingConfirmationEmail($freshGroup, false, $paidNow));
                    Mail::to('reservas@incalake.com')->send(new GroupBookingConfirmationEmail($freshGroup, true, $paidNow));
                }
                \Log::info('Confirmation email sent (PayPal)', [
                    'group_size' => $freshGroup->count(),
                    'booking_ids' => $freshGroup->pluck('id')->all(),
                ]);
            } catch (\Exception $mailException) {
                \Log::error('Failed to send confirmation email (PayPal)', [
                    'booking_ids' => $bookings->pluck('id')->all(),
                    'error' => $mailException->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'booking' => new BookingResource($booking->fresh()),
                'group_booking_ids' => $bookings->pluck('id')->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sibling bookings paid in the same charge (multi-tour cart). Returns a
     * lightweight list so the confirmation page can show every tour of the
     * purchase under a single code. Null/empty for single-tour purchases.
     */
    private function purchaseGroup(Booking $booking)
    {
        $ids = is_array($booking->payment_data ?? null)
            ? ($booking->payment_data['group_booking_ids'] ?? null)
            : null;

        if (!is_array($ids) || count($ids) < 2) {
            return [];
        }

        return Booking::with(['tour', 'tour.mediaGallery', 'pickupDetail'])
            ->whereIn('id', $ids)
            ->orderBy('tour_date')
            ->orderBy('booking_code')
            ->get()
            ->map(fn ($b) => [
                'id'                => $b->id,
                'booking_code'      => $b->booking_code,
                'tour_title'        => $b->tour_title,
                'tour_slug'         => $b->tour?->slug,
                'tour_image'        => $b->tour?->resolveImageUrl(),
                'tour_date'         => $b->tour_date,
                'tour_time'         => $b->tour_time,
                'adults'            => $b->adults,
                'children'          => $b->children,
                'currency'          => $b->currency,
                'total'             => (float) $b->total,
                // Pickup status for the per-tour pickup UI on the confirmation page
                'pickup_configured' => (bool) $b->pickupDetail,
                'pickup_type'       => $b->pickupDetail?->pickup_type,
                'pickup_hotel'      => $b->pickupDetail?->hotel_name,
            ])
            ->values();
    }

    /**
     * Get booking by booking code (REQUIRES EMAIL VERIFICATION)
     *
     * @param Request $request
     * @param string $bookingCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $bookingCode)
    {
        try {
            $booking = Booking::where('booking_code', $bookingCode)
                ->with(['tour', 'tour.mediaGallery'])
                ->firstOrFail();

            // Security check: Require email verification to access booking details
            // This prevents enumeration attacks where someone tries all booking codes
            if ($request->has('email')) {
                $validator = Validator::make($request->all(), [
                    'email' => 'required|email'
                ]);

                if ($validator->fails() || strtolower($request->email) !== strtolower($booking->customer_email)) {
                    \Log::warning('Unauthorized booking access attempt', [
                        'booking_code' => $bookingCode,
                        'attempted_email' => $request->email,
                        'ip' => $request->ip()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Email de verificación incorrecto'
                    ], 403);
                }
            } else {
                // No email provided - return minimal info and request verification
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere verificación de email para ver los detalles de la reserva',
                    'requires_email' => true
                ], 403);
            }

            return response()->json([
                'success' => true,
                'booking' => new BookingResource($booking),
                'group' => $this->purchaseGroup($booking),
                'payment_summary' => $this->paymentSummary($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }
    }

    /**
     * Payment summary for a purchase (group or single): how much was actually
     * charged now vs the balance due, derived from payment_data (what was
     * charged), not the tour's advance config.
     */
    private function paymentSummary(Booking $booking): array
    {
        $pd = is_array($booking->payment_data ?? null) ? $booking->payment_data : [];

        $ids = $pd['group_booking_ids'] ?? null;
        $grandTotal = (is_array($ids) && count($ids) >= 1)
            ? (float) Booking::whereIn('id', $ids)->sum('total')
            : (float) $booking->total;

        $paid = null;
        if (isset($pd['group_total_charged'])) {
            $paid = round(((float) $pd['group_total_charged']) / 100, 2);
        } elseif (isset($pd['group_total_captured'])) {
            $paid = round((float) $pd['group_total_captured'], 2);
        } elseif (isset($pd['charge_data']['amount'])) {
            $paid = round(((float) $pd['charge_data']['amount']) / 100, 2);
        }
        if ($paid === null) {
            $paid = $booking->payment_status === 'paid' ? $grandTotal : 0.0;
        }

        $balance = round(max(0, $grandTotal - $paid), 2);

        return [
            'grand_total' => round($grandTotal, 2),
            'paid_now'    => round($paid, 2),
            'balance_due' => $balance,
            'is_partial'  => $balance > 0.5,
            'currency'    => $booking->currency,
        ];
    }

    /**
     * Get booking by confirmation token (for email links)
     * This method allows access via the secure token sent in confirmation emails
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByToken($token)
    {
        try {
            $booking = Booking::where('confirmation_token', $token)
                ->where('confirmation_token_expires_at', '>', now())
                ->with(['tour', 'tour.mediaGallery'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'booking' => new BookingResource($booking),
                'group' => $this->purchaseGroup($booking),
                'payment_summary' => $this->paymentSummary($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token de confirmación inválido o expirado'
            ], 404);
        }
    }

    /**
     * Cancel a booking
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Confirm a booking (admin). Also re-activates a cancelled booking.
     */
    public function confirm(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            if ($booking->status === 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva ya está confirmada'
                ], 400);
            }

            $booking->confirm();

            return response()->json([
                'success' => true,
                'message' => 'Reserva confirmada exitosamente',
                'booking' => new BookingResource($booking->fresh())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);

            if ($booking->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva ya está cancelada'
                ], 400);
            }

            $booking->cancel($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente',
                'booking' => new BookingResource($booking->fresh())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all bookings with filters and pagination (for admin)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Booking::with(['tour']);

            // Search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('booking_code', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            } else {
                // By default, hide bookings that never completed payment
                $query->where('payment_status', '!=', 'pending');
            }

            // Payment method filter
            if ($request->has('payment_method') && $request->payment_method) {
                $query->where('payment_method', $request->payment_method);
            }

            // Payment state filter — based on the AMOUNT ACTUALLY CHARGED
            // (stored in payment_data), NOT the tour's advance config. Culqi
            // charges the full total; only PayPal may charge an advance. So a
            // booking is "partial" only when what was charged is less than the
            // booking total.
            //   full     = paid in full (charged >= total)
            //   partial  = paid but charged < total (advance only)
            //   refunded = payment_status refunded
            if ($request->filled('payment_state')) {
                $state = $request->payment_state;
                $paidExpr = "COALESCE("
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(payment_data, '$.group_total_charged')) AS DECIMAL(12,2)) / 100,"
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(payment_data, '$.group_total_captured')) AS DECIMAL(12,2)),"
                    . "CAST(JSON_UNQUOTE(JSON_EXTRACT(payment_data, '$.charge_data.amount')) AS DECIMAL(12,2)) / 100"
                    . ")";
                if ($state === 'refunded') {
                    $query->where('payment_status', 'refunded');
                } elseif ($state === 'partial') {
                    $query->where('payment_status', 'paid')
                        ->whereRaw("$paidExpr IS NOT NULL AND $paidExpr < total * 0.99");
                } elseif ($state === 'full') {
                    $query->where('payment_status', 'paid')
                        ->whereRaw("($paidExpr IS NULL OR $paidExpr >= total * 0.99)");
                }
            }

            // Date filter
            if ($request->has('date') && $request->date) {
                $query->whereDate('tour_date', $request->date);
            }

            // Collapse multi-tour purchases to ONE row (the purchase, not each
            // tour). Every booking of a group stores the SAME ordered
            // payment_data.group_booking_ids array, so its first element is a
            // stable group key and the "primary" row is the one whose id equals
            // it. Keep only that primary; singles have no group key -> kept as
            // themselves. This makes pagination count purchases, not tours.
            $query->whereRaw(
                "(JSON_LENGTH(payment_data, '$.group_booking_ids') IS NULL"
                . " OR JSON_LENGTH(payment_data, '$.group_booking_ids') < 2"
                . " OR id = CAST(JSON_EXTRACT(payment_data, '$.group_booking_ids[0]') AS UNSIGNED))"
            );

            // Order by created_at desc
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $bookings = $query->paginate($perPage);

            // Enrich each multi-tour primary row with the purchase totals so the
            // admin list shows the real amount charged and a "N tours" badge.
            $bookings->getCollection()->transform(function ($b) {
                $ids = is_array($b->payment_data ?? null)
                    ? ($b->payment_data['group_booking_ids'] ?? null)
                    : null;

                if (is_array($ids) && count($ids) >= 2) {
                    $siblings = Booking::whereIn('id', $ids)->get(['total', 'tour_date']);
                    $b->group_count = $siblings->count();
                    $b->group_total = (float) $siblings->sum('total');
                    $b->is_group     = true;
                } else {
                    $b->group_count = 1;
                    $b->is_group    = false;
                }

                // Derived payment state from the AMOUNT ACTUALLY CHARGED
                // (payment_data), not the tour's advance config — Culqi charges
                // the full total, only PayPal may charge an advance.
                $pd = is_array($b->payment_data ?? null) ? $b->payment_data : [];
                $paid = null;
                if (isset($pd['group_total_charged'])) {
                    $paid = round(((float) $pd['group_total_charged']) / 100, 2); // Culqi cents
                } elseif (isset($pd['group_total_captured'])) {
                    $paid = round((float) $pd['group_total_captured'], 2);          // PayPal units
                } elseif (isset($pd['charge_data']['amount'])) {
                    $paid = round(((float) $pd['charge_data']['amount']) / 100, 2);
                } elseif (isset($pd['amount_cents'])) {
                    $paid = round(((float) $pd['amount_cents']) / 100, 2);
                }

                $expectedFull = $b->is_group ? (float) $b->group_total : (float) $b->total;

                if ($b->payment_status === 'refunded') {
                    $b->payment_state = 'refunded';
                } elseif ($b->payment_status === 'paid') {
                    if ($paid !== null && $expectedFull > 0 && $paid < ($expectedFull - 0.5)) {
                        $b->payment_state = 'partial';
                        $b->amount_paid = $paid;
                        $b->amount_remaining = round($expectedFull - $paid, 2);
                    } else {
                        $b->payment_state = 'full';
                    }
                } else {
                    $b->payment_state = 'unpaid';
                }

                return $b;
            });

            return response()->json($bookings);

        } catch (\Exception $e) {
            \Log::error('Error fetching bookings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las reservas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
