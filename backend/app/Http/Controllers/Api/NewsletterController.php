<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * Public: footer newsletter signup. Idempotent — signing up twice is a
     * success, not an error, and re-subscribing clears a previous opt-out.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email|max:160',
            'language' => 'nullable|string|max:5',
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower(trim($data['email']))],
            ['language' => $data['language'] ?? null, 'source' => 'footer', 'unsubscribed_at' => null],
        );

        return response()->json([
            'success' => true,
            'message' => 'Suscripción registrada.',
        ], 201);
    }
}
