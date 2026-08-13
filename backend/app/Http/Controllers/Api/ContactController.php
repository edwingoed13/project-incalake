<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Public: message from the "Contacto" form. Persists it first, then emails
     * reservas@incalake.com — so a mail outage never loses a customer message
     * (the previous frontend just faked a 1s delay and claimed success).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:160',
            'phone'    => 'nullable|string|max:40',
            'message'  => 'required|string|max:3000',
            'language' => 'nullable|string|max:5',
        ]);

        $contact = ContactMessage::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'message'  => $data['message'],
            'language' => $data['language'] ?? null,
            'status'   => 'new',
        ]);

        try {
            Mail::to(config('services.incalake.reservations_email'))->send(new ContactMessageMail($contact));
        } catch (\Throwable $e) {
            Log::error('Contact message email failed', ['id' => $contact->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado. Te responderemos pronto.',
        ], 201);
    }
}
