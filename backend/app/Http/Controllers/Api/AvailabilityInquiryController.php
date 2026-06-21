<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AvailabilityInquiryMail;
use App\Models\AvailabilityInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AvailabilityInquiryController extends Controller
{
    /**
     * Public: capture an availability request from a tour that requires
     * verification. Persists the lead, then emails reservas@incalake.com (mail
     * failure never loses the lead).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour_id'        => 'nullable|integer|exists:tours,id',
            'tour_title'     => 'nullable|string|max:255',
            'name'           => 'required|string|max:120',
            'email'          => 'required|email|max:160',
            'phone'          => 'nullable|string|max:40',
            'preferred_date' => 'nullable|date',
            'adults'         => 'nullable|integer|min:1|max:99',
            'children'       => 'nullable|integer|min:0|max:99',
            'message'        => 'nullable|string|max:2000',
            'language'       => 'nullable|string|max:5',
        ]);

        $inquiry = AvailabilityInquiry::create([
            'tour_id'        => $data['tour_id'] ?? null,
            'tour_title'     => $data['tour_title'] ?? null,
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'adults'         => $data['adults'] ?? 1,
            'children'       => $data['children'] ?? 0,
            'message'        => $data['message'] ?? null,
            'language'       => $data['language'] ?? null,
            'status'         => 'new',
        ]);

        try {
            Mail::to('reservas@incalake.com')->send(new AvailabilityInquiryMail($inquiry));
        } catch (\Throwable $e) {
            Log::error('Availability inquiry email failed', ['id' => $inquiry->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Consulta enviada. Te responderemos pronto.',
        ], 201);
    }
}
