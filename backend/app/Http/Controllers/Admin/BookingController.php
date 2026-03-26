<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.bookings.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tours = Tour::where('active', true)->get();

        return view('admin.bookings.create', compact('tours'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'total_participants' => 'required|integer|min:1',
            'tour_date' => 'required|date',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'required|in:pending,paid,refunded',
            'admin_notes' => 'nullable|string',
        ]);

        // Generate booking code
        $booking_code = Booking::generateBookingCode();

        $booking = Booking::create([
            'booking_code' => $booking_code,
            'tour_id' => $validated['tour_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'total_participants' => $validated['total_participants'],
            'tour_date' => $validated['tour_date'],
            'total' => $validated['total'],
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'admin_notes' => $validated['admin_notes'],
        ]);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Reserva creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $booking->load(['tour']);

        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        $booking->load(['tour']);

        $tours = Tour::where('active', true)->get();

        return view('admin.bookings.edit', compact('booking', 'tours'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'total_participants' => 'required|integer|min:1',
            'tour_date' => 'required|date',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'required|in:pending,paid,refunded',
            'admin_notes' => 'nullable|string',
        ]);

        $booking->update($validated);

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Reserva actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Reserva eliminada exitosamente');
    }
}
