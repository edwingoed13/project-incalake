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

            // Prepare Culqi charge data
            $chargeData = [
                'amount' => $amountInCents,
                'currency_code' => $booking->currency,
                'email' => $booking->customer_email,
                'source_id' => $culqiToken,
                'description' => "Reserva {$booking->booking_code} - {$booking->tour_title}"
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

            // TODO: Send confirmation email
            // dispatch(new SendBookingConfirmationEmail($booking));

            return response()->json([
                'success' => true,
                'message' => 'Pago confirmado exitosamente',
                'booking' => new BookingResource($booking->fresh()),
                'charge_id' => $chargeId
            ]);

        } catch (\Exception $e) {
            \Log::error('Culqi payment exception', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }
