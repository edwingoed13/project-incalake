<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Public: Get reviews for a tour or featured reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::where('published', true);

        if ($request->has('tour_id')) {
            $query->where('tour_id', $request->tour_id);
        }

        if ($request->has('featured')) {
            $query->where('featured', true);
        }

        // Include tour info for linking
        $query->with(['tour:id,code,city_id', 'tour.translations' => function ($q) use ($request) {
            $langCode = strtoupper($request->language ?? 'ES');
            $q->select('tour_id', 'slug', 'language_id', 'h1_title')
              ->whereHas('language', fn($q2) => $q2->where('code', $langCode));
        }, 'tour.city:id,slug,name']);

        if ($request->has('language')) {
            $query->where('language', strtolower($request->language));
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $query->orderByDesc('created_at')->orderByDesc('rating');

        $perPage = $request->get('per_page', 10);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'total' => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
            ],
        ]);
    }

    /**
     * Admin: List all reviews with filters.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Review::with('tour:id,code');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%")
                  ->orWhere('opinion', 'like', "%{$search}%");
            });
        }

        if ($request->has('published')) {
            $query->where('published', $request->boolean('published'));
        }

        if ($request->has('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('tour_id')) {
            if ($request->tour_id === 'unassigned') {
                $query->whereNull('tour_id');
            } else {
                $query->where('tour_id', $request->tour_id);
            }
        }

        $query->orderByDesc('created_at');

        $perPage = $request->get('per_page', 20);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'total' => $reviews->total(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
            ],
        ]);
    }

    /**
     * Admin: Create a review.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'comment' => 'required|string',
            'rating' => 'integer|min:1|max:5',
        ]);

        $review = Review::create([
            'tour_id' => $request->tour_id,
            'name' => $request->name,
            'review_date' => $request->review_date,
            'rating' => $request->rating ?? 5,
            'title' => $request->title,
            'comment' => $request->comment,
            'language' => $request->language ?? 'en',
            'opinion' => $request->opinion,
            'published' => $request->boolean('published', true),
            'featured' => $request->boolean('featured', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review created.',
            'data' => $review,
        ], 201);
    }

    /**
     * Admin: Update a review.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        $review->update($request->only([
            'tour_id', 'name', 'review_date', 'rating', 'title',
            'comment', 'language', 'opinion', 'published', 'featured',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Review updated.',
            'data' => $review,
        ]);
    }

    /**
     * Admin: Delete a review.
     */
    public function destroy(int $id): JsonResponse
    {
        Review::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted.',
        ]);
    }

    /**
     * Admin: Get stats.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => Review::count(),
                'published' => Review::where('published', true)->count(),
                'featured' => Review::where('featured', true)->count(),
                'avg_rating' => round(Review::avg('rating'), 1),
                'by_rating' => [
                    5 => Review::where('rating', 5)->count(),
                    4 => Review::where('rating', 4)->count(),
                ],
                'unassigned' => Review::whereNull('tour_id')->count(),
            ],
        ]);
    }
}
