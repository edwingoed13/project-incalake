<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', true)->count(),
            'total_categories' => Category::distinct('category_code_id')->count(),
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_revenue' => Booking::where('payment_status', 'paid')->sum('total'),
        ];

        // Recent bookings
        $recentBookings = Booking::latest()
            ->limit(5)
            ->get();

        // Popular tours (products)
        $popularProducts = Product::with('categories')
            ->select('products.*')
            ->selectRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.tour_id = products.id AND bookings.deleted_at IS NULL) as bookings_count')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get();

        // Monthly revenue
        $monthlyRevenue = Booking::where('payment_status', 'paid')
            ->whereYear('tour_date', date('Y'))
            ->selectRaw('MONTH(tour_date) as month, SUM(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentBookings',
            'popularProducts',
            'monthlyRevenue'
        ));
    }
}
