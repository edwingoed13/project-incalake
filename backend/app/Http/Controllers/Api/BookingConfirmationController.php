<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPickupDetail;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingConfirmationController extends Controller
{
    /**
     * Validate hotel location and calculate if it's within radius
     */
    public function validateHotelLocation(Request $request, $bookingId)
    {
        $request->validate([
            'hotel_lat' => 'required|numeric',
            'hotel_lng' => 'required|numeric',
            'hotel_name' => 'required|string',
            'hotel_address' => 'required|string',
            'hotel_place_id' => 'nullable|string'
        ]);

        try {
            $booking = Booking::with('tour')->findOrFail($bookingId);
            $tour = $booking->tour;

            // Check if tour has hotel pickup enabled
            if (!$tour->enable_hotel_pickup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este tour no tiene habilitado el recojo de hotel'
                ], 400);
            }

            // Calculate distance from pickup center
            $distance = $this->calculateDistance(
                $request->hotel_lat,
                $request->hotel_lng,
                $tour->pickup_center_lat,
                $tour->pickup_center_lng
            );

            $isWithinRadius = $distance <= $tour->pickup_radius_km;
            $extraCost = 0;

            if (!$isWithinRadius) {
                // Calculate extra cost for pickup outside radius
                $extraDistance = $distance - $tour->pickup_radius_km;
                $costPerKm = 5; // $5 USD per km
                $extraCost = $extraDistance * $costPerKm;

                // Apply min and max limits
                $minCost = 15;
                $maxCost = 50;
                $extraCost = max($minCost, min($maxCost, $extraCost));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'is_within_radius' => $isWithinRadius,
                    'distance' => round($distance, 2),
                    'radius_km' => $tour->pickup_radius_km,
                    'extra_cost' => round($extraCost, 2),
                    'hotel_name' => $request->hotel_name,
                    'hotel_address' => $request->hotel_address,
                    'requires_approval' => $distance > 5,
                    'meeting_point' => [
                        'description' => $tour->meeting_point_description,
                        'lat' => $tour->meeting_point_lat,
                        'lng' => $tour->meeting_point_lng
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating hotel location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al validar la ubicación del hotel'
            ], 500);
        }
    }

    /**
     * Save pickup configuration details
     */
    public function savePickupDetails(Request $request, $bookingId)
    {
        $request->validate([
            'final_choice' => 'required|in:hotel_pickup,meeting_point',
            'hotel_name' => 'required_if:final_choice,hotel_pickup',
            'hotel_address' => 'required_if:final_choice,hotel_pickup',
            'hotel_lat' => 'required_if:final_choice,hotel_pickup|numeric',
            'hotel_lng' => 'required_if:final_choice,hotel_pickup|numeric',
            'hotel_place_id' => 'nullable|string',
            'distance' => 'required_if:final_choice,hotel_pickup|numeric',
            'extra_cost' => 'nullable|numeric'
        ]);

        DB::beginTransaction();
        try {
            $booking = Booking::with('tour')->findOrFail($bookingId);

            // Delete existing pickup detail if any
            BookingPickupDetail::where('booking_id', $bookingId)->delete();

            $pickupDetail = new BookingPickupDetail();
            $pickupDetail->booking_id = $bookingId;
            $pickupDetail->final_choice = $request->final_choice;

            if ($request->final_choice === 'hotel_pickup') {
                $tour = $booking->tour;
                $distance = $request->distance;
                $isWithinRadius = $distance <= $tour->pickup_radius_km;

                $pickupDetail->hotel_name = $request->hotel_name;
                $pickupDetail->hotel_address = $request->hotel_address;
                $pickupDetail->hotel_place_id = $request->hotel_place_id;
                $pickupDetail->hotel_lat = $request->hotel_lat;
                $pickupDetail->hotel_lng = $request->hotel_lng;
                $pickupDetail->distance_from_center = $distance;

                if ($isWithinRadius) {
                    $pickupDetail->pickup_type = 'hotel_within_radius';
                    $pickupDetail->extra_pickup_cost = 0;
                } else {
                    $pickupDetail->pickup_type = 'hotel_extra_charge';
                    $pickupDetail->extra_pickup_cost = $request->extra_cost ?? 0;
                    $pickupDetail->requires_logistics_approval = $distance > 5;

                    if ($pickupDetail->requires_logistics_approval) {
                        $pickupDetail->approval_status = 'pending';
                    }
                }
            } else {
                $pickupDetail->pickup_type = 'meeting_point';
            }

            $pickupDetail->save();

            // If requires logistics approval, send notification
            if ($pickupDetail->requires_logistics_approval) {
                $this->notifyLogisticsTeam($booking, $pickupDetail);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuración de recojo guardada exitosamente',
                'data' => $pickupDetail
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving pickup details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la configuración de recojo'
            ], 500);
        }
    }

    /**
     * Get pickup details for a booking
     */
    public function getPickupDetails($bookingId)
    {
        try {
            $pickupDetail = BookingPickupDetail::where('booking_id', $bookingId)->first();
            $booking = Booking::with('tour')->findOrFail($bookingId);

            return response()->json([
                'success' => true,
                'data' => [
                    'tour_config' => [
                        'enable_meeting_point' => $booking->tour->enable_meeting_point,
                        'enable_hotel_pickup' => $booking->tour->enable_hotel_pickup,
                        'meeting_point_description' => $booking->tour->meeting_point_description,
                        'meeting_point_lat' => $booking->tour->meeting_point_lat,
                        'meeting_point_lng' => $booking->tour->meeting_point_lng,
                        'pickup_center_lat' => $booking->tour->pickup_center_lat,
                        'pickup_center_lng' => $booking->tour->pickup_center_lng,
                        'pickup_radius_km' => $booking->tour->pickup_radius_km,
                    ],
                    'pickup_detail' => $pickupDetail
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting pickup details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de recojo'
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Notify logistics team about pickup request outside radius
     */
    private function notifyLogisticsTeam($booking, $pickupDetail)
    {
        Log::info('Logistics notification needed for booking #' . $booking->id, [
            'hotel' => $pickupDetail->hotel_name,
            'distance' => $pickupDetail->distance_from_center,
            'extra_cost' => $pickupDetail->extra_pickup_cost
        ]);
    }

    /**
     * Save travelers for a booking
     */
    public function saveTravelers(Request $request, $bookingId)
    {
        $request->validate([
            'travelers' => 'required|array|min:1',
            'travelers.*.full_name' => 'required|string|max:150',
            'travelers.*.nationality' => 'nullable|string|max:80',
            'travelers.*.doc_type' => 'nullable|string|in:passport,dni',
            'travelers.*.doc_number' => 'nullable|string|max:50',
            'travelers.*.age_group' => 'nullable|string|in:adult,child,infant',
            'travelers.*.special_needs' => 'nullable|string|max:500',
        ]);

        try {
            $booking = Booking::findOrFail($bookingId);

            // Delete existing travelers and recreate
            $booking->travelers()->delete();

            foreach ($request->travelers as $idx => $data) {
                $booking->travelers()->create([
                    'full_name' => $data['full_name'],
                    'nationality' => $data['nationality'] ?? null,
                    'doc_type' => $data['doc_type'] ?? 'passport',
                    'doc_number' => $data['doc_number'] ?? null,
                    'age_group' => $data['age_group'] ?? 'adult',
                    'special_needs' => $data['special_needs'] ?? null,
                    'is_leader' => $idx === 0,
                    'order' => $idx,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Travelers saved successfully.',
                'data' => $booking->travelers()->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving travelers', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error saving travelers: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get travelers for a booking
     */
    public function getTravelers($bookingId)
    {
        $booking = Booking::with('travelers')->findOrFail($bookingId);

        return response()->json([
            'success' => true,
            'data' => $booking->travelers,
        ]);
    }

    /**
     * Get full booking details including pickup and travelers (for admin)
     */
    public function getFullDetails($bookingId)
    {
        $booking = Booking::with(['tour', 'pickupDetail', 'travelers'])->findOrFail($bookingId);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'pickup_detail' => $booking->pickupDetail,
                'travelers' => $booking->travelers,
            ],
        ]);
    }
}