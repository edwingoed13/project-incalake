<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewController extends Controller
{
    /**
     * Public: Google Places reviews for the homepage.
     *
     * The Google Place Details endpoint returns up to 5 reviews + the aggregate
     * rating. We cache the result for 12h so we never hit Google on a per-visit
     * basis (cost + rate limits), and the homepage fetch stays fast. When the
     * integration isn't configured (no place_id / api_key) we return an empty,
     * well-formed payload so the frontend just hides the section.
     */
    public function index(Request $request): JsonResponse
    {
        $placeId = config('services.google_places.place_id');
        $apiKey  = config('services.google_places.api_key');

        if (! $placeId || ! $apiKey) {
            return response()->json([
                'success'    => true,
                'configured' => false,
                'data'       => [],
                'rating'     => null,
                'total'      => 0,
            ]);
        }

        // Google localizes the relative_time_description ("hace 2 meses"); cache
        // per language so each locale gets its own copy.
        $lang = strtolower($request->get('language', 'es'));
        $cacheKey = "google_reviews:{$placeId}:{$lang}";

        $payload = Cache::remember($cacheKey, now()->addHours(12), function () use ($placeId, $apiKey, $lang) {
            try {
                $res = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $placeId,
                    'fields'   => 'rating,user_ratings_total,reviews,url',
                    'reviews_sort' => 'newest',
                    'language' => $lang,
                    'key'      => $apiKey,
                ]);

                $json = $res->json();
                if (($json['status'] ?? '') !== 'OK') {
                    Log::warning('Google Places reviews fetch failed', [
                        'status' => $json['status'] ?? 'no-status',
                        'error'  => $json['error_message'] ?? null,
                    ]);
                    return null;
                }

                $result  = $json['result'] ?? [];
                $reviews = collect($result['reviews'] ?? [])
                    ->filter(fn ($r) => ! empty($r['text']))
                    ->map(fn ($r) => [
                        'author_name'        => $r['author_name'] ?? '',
                        'profile_photo_url'  => $r['profile_photo_url'] ?? null,
                        'rating'             => (int) ($r['rating'] ?? 0),
                        'text'               => $r['text'] ?? '',
                        'relative_time'      => $r['relative_time_description'] ?? '',
                        'time'               => $r['time'] ?? 0,
                        'author_url'         => $r['author_url'] ?? null,
                    ])
                    ->values()
                    ->all();

                return [
                    'data'   => $reviews,
                    'rating' => isset($result['rating']) ? round((float) $result['rating'], 1) : null,
                    'total'  => (int) ($result['user_ratings_total'] ?? 0),
                    'url'    => $result['url'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::error('Google Places reviews exception: '.$e->getMessage());
                return null;
            }
        });

        // A failed fetch caches null briefly; degrade gracefully without caching
        // the failure for 12h.
        if ($payload === null) {
            Cache::forget($cacheKey);
            return response()->json([
                'success'    => true,
                'configured' => true,
                'data'       => [],
                'rating'     => null,
                'total'      => 0,
            ]);
        }

        return response()->json([
            'success'    => true,
            'configured' => true,
            'data'       => $payload['data'],
            'rating'     => $payload['rating'],
            'total'      => $payload['total'],
            'place_url'  => $payload['url'] ?? null,
        ]);
    }
}
