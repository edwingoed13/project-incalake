<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * PayPal REST API v2 Service
 *
 * Uses Laravel's built-in HTTP client (Guzzle).
 * No additional SDK required.
 */
class PayPalService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected string $mode;

    public function __construct()
    {
        $this->clientId = (string) config('services.paypal.client_id', '');
        $this->clientSecret = (string) config('services.paypal.client_secret', '');
        $this->mode = (string) config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get access token for PayPal API (cached for 8 hours).
     */
    protected function getAccessToken(): string
    {
        $cacheKey = "paypal_access_token_{$this->mode}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        if (!$this->clientId || !$this->clientSecret) {
            throw new Exception('PayPal credentials not configured');
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$response->successful()) {
            Log::error('PayPal auth failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('PayPal authentication failed');
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 28800);

        if (!$token) {
            throw new Exception('Invalid PayPal auth response');
        }

        // Cache for slightly less than expires_in to avoid edge cases
        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    /**
     * Get details of a PayPal order (before capture).
     * Used to validate amount matches booking total.
     */
    public function getOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if (!$response->successful()) {
            Log::error('PayPal getOrder failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception("Cannot retrieve PayPal order: {$orderId}");
        }

        return $response->json();
    }

    /**
     * Capture an approved PayPal order.
     * Returns the capture response with transaction details.
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                // Idempotency: retry-safe capture
                'PayPal-Request-Id' => "capture-{$orderId}",
            ])
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if (!$response->successful()) {
            Log::error('PayPal capture failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('Failed to capture PayPal payment: ' . ($response->json()['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Extract the captured amount from a capture response.
     * PayPal structure: purchase_units[0].payments.captures[0].amount
     */
    public function getCapturedAmount(array $captureResponse): ?float
    {
        $captures = $captureResponse['purchase_units'][0]['payments']['captures'] ?? [];
        if (empty($captures)) return null;

        $amount = $captures[0]['amount']['value'] ?? null;
        return $amount !== null ? (float) $amount : null;
    }

    /**
     * Extract transaction ID from a capture response.
     */
    public function getTransactionId(array $captureResponse): ?string
    {
        $captures = $captureResponse['purchase_units'][0]['payments']['captures'] ?? [];
        if (empty($captures)) return null;

        return $captures[0]['id'] ?? null;
    }

    /**
     * Extract capture status ("COMPLETED" means successful).
     */
    public function getCaptureStatus(array $captureResponse): string
    {
        $captures = $captureResponse['purchase_units'][0]['payments']['captures'] ?? [];
        if (empty($captures)) return 'UNKNOWN';

        return (string) ($captures[0]['status'] ?? 'UNKNOWN');
    }
}
