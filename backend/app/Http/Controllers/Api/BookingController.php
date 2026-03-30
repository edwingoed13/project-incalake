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
            'customer_name' => 'required|string|max:255',
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

            // Ensure minimum amount for Culqi (requires at least $1.00 = 100 cents)
            if ($total < 1.00) {
                $total = 1.00;
                $subtotal = 1.00;
            }

            // Generate unique booking code
            $bookingCode = Booking::generateBookingCode();

            // Create booking
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'tour_id' => $tour->id,
                'tour_title' => $tourTitle,
                'tour_date' => $request->tour_date,
                'tour_time' => $request->tour_time,
                'customer_name' => $request->customer_name,
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
                'total' => $total,
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);

            if ($booking->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva ya ha sido pagada'
                ], 400);
            }

            // Integrate with Culqi API to create charge
            $culqiToken = $request->token;
            $secretKey = config('services.culqi.secret_key');

            // Convert amount to cents (Culqi requires integer in cents)
            $amountInCents = (int) ($booking->total * 100);

            // Prepare description (Culqi requires 5-80 characters)
            $description = "Reserva {$booking->booking_code}";
            if ($booking->tour_title) {
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
                'description' => $description
            ];

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

            // Mark booking as paid
            $booking->markAsPaid($chargeId, [
                'token' => $culqiToken,
                'gateway' => 'culqi',
                'charge_data' => $chargeResponse,
                'payment_data' => $request->payment_data
            ]);

            \Log::info('Booking marked as paid', [
                'booking_id' => $booking->id,
                'charge_id' => $chargeId
            ]);

            // Send confirmation emails (only on successful payment, non-blocking)
            try {
                Mail::to($booking->customer_email)->send(new BookingConfirmationEmail($booking, false));
                Mail::to('reservas@incalake.com')->send(new BookingConfirmationEmail($booking, true));
                \Log::info('Booking confirmation emails sent', ['booking_id' => $booking->id]);
            } catch (\Exception $mailException) {
                \Log::error('Failed to send booking confirmation email', [
                    'booking_id' => $booking->id,
                    'error' => $mailException->getMessage()
                ]);
            }

            // Add event to admin Google Calendar (non-blocking)
            try {
                $calendarService = new GoogleCalendarService();
                $calendarService->createBookingEvent([
                    'booking_code'    => $booking->booking_code,
                    'tour_title'      => $booking->tour_title,
                    'tour_date'       => \Carbon\Carbon::parse($booking->tour_date)->format('Y-m-d'),
                    'tour_time'       => \Carbon\Carbon::parse($booking->tour_time)->format('H:i:s'),
                    'adults'          => $booking->adults,
                    'children'        => $booking->children,
                    'customer_name'   => $booking->customer_name,
                    'customer_email'  => $booking->customer_email,
                    'customer_phone'  => $booking->customer_phone,
                    'total'           => $booking->total,
                    'currency'        => $booking->currency,
                    'payment_method'  => $booking->payment_method ?? 'culqi',
                ]);
            } catch (\Exception $calException) {
                \Log::error('Failed to create Google Calendar event', [
                    'booking_id' => $booking->id,
                    'error' => $calException->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'booking' => new BookingResource($booking->fresh()),
                'charge_id' => $chargeId
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
    public function confirmPayPalPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'payment_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);

            if ($booking->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva ya ha sido pagada'
                ], 400);
            }

            // TODO: Integrate with PayPal API to capture payment
            // For now, we'll simulate a successful payment
            $orderId = $request->order_id;

            // Here you would call PayPal API to capture the order
            // $response = PayPal::captureOrder($orderId);

            // Mark booking as paid
            $booking->markAsPaid($orderId, [
                'order_id' => $orderId,
                'gateway' => 'paypal',
                'data' => $request->payment_data
            ]);

            // Send confirmation emails (only on successful payment, non-blocking)
            try {
                Mail::to($booking->customer_email)->send(new BookingConfirmationEmail($booking, false));
                Mail::to('reservas@incalake.com')->send(new BookingConfirmationEmail($booking, true));
                \Log::info('Booking confirmation emails sent (PayPal)', ['booking_id' => $booking->id]);
            } catch (\Exception $mailException) {
                \Log::error('Failed to send booking confirmation email (PayPal)', [
                    'booking_id' => $booking->id,
                    'error' => $mailException->getMessage()
                ]);
            }

            // Add event to admin Google Calendar (non-blocking)
            try {
                $calendarService = new GoogleCalendarService();
                $calendarService->createBookingEvent([
                    'booking_code'    => $booking->booking_code,
                    'tour_title'      => $booking->tour_title,
                    'tour_date'       => \Carbon\Carbon::parse($booking->tour_date)->format('Y-m-d'),
                    'tour_time'       => \Carbon\Carbon::parse($booking->tour_time)->format('H:i:s'),
                    'adults'          => $booking->adults,
                    'children'        => $booking->children,
                    'customer_name'   => $booking->customer_name,
                    'customer_email'  => $booking->customer_email,
                    'customer_phone'  => $booking->customer_phone,
                    'total'           => $booking->total,
                    'currency'        => $booking->currency,
                    'payment_method'  => 'paypal',
                ]);
            } catch (\Exception $calException) {
                \Log::error('Failed to create Google Calendar event (PayPal)', [
                    'booking_id' => $booking->id,
                    'error' => $calException->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'booking' => new BookingResource($booking->fresh())
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
                ->with('tour')
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
                'booking' => new BookingResource($booking)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }
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
                ->with('tour')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'booking' => new BookingResource($booking)
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

            // Date filter
            if ($request->has('date') && $request->date) {
                $query->whereDate('tour_date', $request->date);
            }

            // Order by created_at desc
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $bookings = $query->paginate($perPage);

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
