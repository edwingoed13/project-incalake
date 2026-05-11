<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Revenue this month (only paid bookings)
        $revenueThisMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $revenueLastMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');

        $revenueTrend = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Bookings this month (paid only)
        $bookingsThisMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $bookingsLastMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $bookingsTrend = $bookingsLastMonth > 0
            ? round((($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100, 1)
            : ($bookingsThisMonth > 0 ? 100 : 0);

        // Total passengers (pax) from paid bookings
        $totalPax = Booking::where('payment_status', 'paid')
            ->sum('total_participants');

        $paxThisMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_participants');

        $paxLastMonth = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_participants');

        $paxTrend = $paxLastMonth > 0
            ? round((($paxThisMonth - $paxLastMonth) / $paxLastMonth) * 100, 1)
            : ($paxThisMonth > 0 ? 100 : 0);

        // Total translations across all tours
        $totalTranslations = TourTranslation::count();
        $totalTours = Tour::count();

        return response()->json([
            'revenue' => [
                'value' => number_format($revenueThisMonth, 2),
                'trend' => $revenueTrend,
            ],
            'bookings' => [
                'value' => $bookingsThisMonth,
                'trend' => $bookingsTrend,
            ],
            'pax' => [
                'value' => $totalPax,
                'month' => $paxThisMonth,
                'trend' => $paxTrend,
            ],
            'tours' => [
                'value' => $totalTours,
                'translations' => $totalTranslations,
                'trend' => 0,
            ],
        ]);
    }

    public function recentBookings(): JsonResponse
    {
        $bookings = Booking::where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'booking_code', 'customer_name', 'tour_title', 'total', 'currency', 'payment_status', 'status', 'created_at']);

        return response()->json($bookings);
    }

    public function salesChart(): JsonResponse
    {
        $end = Carbon::now()->endOfMonth();
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $rows = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total) as revenue, COUNT(*) as bookings")
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $monthLabels = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
        $series = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $ym = $cursor->format('Y-m');
            $row = $rows->get($ym);

            $series[] = [
                'ym' => $ym,
                'label' => $monthLabels[$cursor->month - 1],
                'year' => $cursor->year,
                'revenue' => $row ? (float) $row->revenue : 0,
                'bookings' => $row ? (int) $row->bookings : 0,
            ];
            $cursor->addMonth();
        }

        $totalRevenue = array_sum(array_column($series, 'revenue'));
        $totalBookings = array_sum(array_column($series, 'bookings'));
        $maxRevenue = max(array_column($series, 'revenue')) ?: 0;

        return response()->json([
            'series' => $series,
            'totals' => [
                'revenue' => $totalRevenue,
                'bookings' => $totalBookings,
                'max_revenue' => $maxRevenue,
            ],
        ]);
    }
}
