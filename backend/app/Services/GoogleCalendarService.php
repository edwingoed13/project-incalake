<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private string $credentialsPath;
    private string $calendarId;
    private Client $http;

    public function __construct()
    {
        $this->credentialsPath = storage_path('app/google-calendar-credentials.json');
        $this->calendarId = config('services.google_calendar.calendar_id', 'reservas@incalake.com');
        $this->http = new Client(['timeout' => 15]);
    }

    /**
     * Create a calendar event for a booking (admin calendar).
     */
    public function createBookingEvent(array $booking): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('GoogleCalendar: could not obtain access token');
                return false;
            }

            $event = $this->buildEvent($booking);

            $response = $this->http->post(
                "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($this->calendarId) . "/events",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$accessToken}",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $event,
                ]
            );

            $body = json_decode($response->getBody(), true);
            Log::info('GoogleCalendar: event created', ['event_id' => $body['id'] ?? null, 'booking_code' => $booking['booking_code']]);
            return true;

        } catch (\Exception $e) {
            Log::error('GoogleCalendar: failed to create event', [
                'booking_code' => $booking['booking_code'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build the Google Calendar event body.
     * Title: "Nombre (N pax) | Tour | CULQI/PAYPAL"
     * Color: graphite (plomo)
     * Duration: 30 minutes
     */
    private function buildEvent(array $booking): array
    {
        $paymentMethod = strtoupper($booking['payment_method'] ?? 'CULQI');
        $participants  = (int)($booking['adults'] ?? 0) + (int)($booking['children'] ?? 0);
        $title = "{$booking['customer_name']} ({$participants}) | {$booking['tour_title']} | {$paymentMethod}";

        // Build start datetime: extract only Y-m-d from tour_date, then combine with tour_time
        $tourDate = date('Y-m-d', strtotime($booking['tour_date'] ?? date('Y-m-d')));
        $tourTime = substr($booking['tour_time'] ?? '09:00:00', 0, 8);
        $timeZone = 'America/Lima';

        $startDt = new \DateTime("{$tourDate} {$tourTime}", new \DateTimeZone($timeZone));
        $endDt   = clone $startDt;
        $endDt->modify('+30 minutes');

        $description = implode("\n", [
            "Reserva: {$booking['booking_code']}",
            "Cliente: {$booking['customer_name']}",
            "Teléfono: " . ($booking['customer_phone'] ?? '-'),
            "Email: " . ($booking['customer_email'] ?? '-'),
            "Adultos: " . ($booking['adults'] ?? 0) . " | Niños: " . ($booking['children'] ?? 0),
            "Total: " . ($booking['currency'] ?? 'USD') . " " . number_format($booking['total'] ?? 0, 2),
            "Pago: {$paymentMethod}",
        ]);

        return [
            'summary'     => $title,
            'description' => $description,
            'colorId'     => '8', // graphite (plomo)
            'start' => [
                'dateTime' => $startDt->format(\DateTime::RFC3339),
                'timeZone' => $timeZone,
            ],
            'end' => [
                'dateTime' => $endDt->format(\DateTime::RFC3339),
                'timeZone' => $timeZone,
            ],
        ];
    }

    /**
     * Generate a JWT and exchange it for a Google OAuth2 access token.
     */
    private function getAccessToken(): ?string
    {
        $credentials = json_decode(file_get_contents($this->credentialsPath), true);

        $now    = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim  = base64url_encode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ]));

        $signingInput = "{$header}.{$claim}";
        $privateKey   = $credentials['private_key'];

        openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
        $jwt = "{$signingInput}." . base64url_encode($signature);

        $response = $this->http->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'] ?? null;
    }
}

// Helper function (PHP < 8.2 compatible)
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
