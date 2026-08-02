<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-off gallery re-migration (admin-gated). The original legacy import
 * copied the 800x400 display variants instead of the full-resolution photos
 * that live next to them in the legacy galeria. A local script re-uploads the
 * originals through this endpoint:
 *
 *   POST /api/admin/tours/{id}/replace-gallery   (multipart)
 *     image        required image file (jpeg/png/webp, up to 8MB)
 *     clear_first  "1" on the FIRST upload of a tour: deletes the existing
 *                  gallery rows + files before inserting
 *     is_primary   "1" to mark as featured
 *     order        integer position
 *     alt_text     optional
 */
class TourGalleryRemigrationController extends Controller
{
    public function replaceGallery(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:8192',
            'clear_first' => 'nullable|boolean',
            'is_primary' => 'nullable|boolean',
            'order' => 'nullable|integer|min:1',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $tour = Tour::with('mediaGallery')->findOrFail($id);
        $dir = 'tours/' . $tour->id;

        $cleared = 0;
        if ($request->boolean('clear_first')) {
            foreach ($tour->mediaGallery as $media) {
                foreach (array_unique(array_filter([$media->image_path, $media->original_path])) as $path) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Throwable $e) {
                        // A missing file must not block the re-migration.
                    }
                }
                $media->delete();
                $cleared++;
            }
        }

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($dir, $filename, 'public');

        $media = $tour->mediaGallery()->create([
            'language_id' => $tour->primary_language_id,
            'image_path' => $path,
            'original_path' => $path,
            'alt_text' => $request->input('alt_text') ?: ($tour->code . ' - Image'),
            'title_text' => $tour->translations->first()->h1_title ?? 'Tour Image',
            'is_primary' => $request->boolean('is_primary'),
            'order' => (int) $request->input('order', 1),
        ]);

        Log::info('Gallery re-migration upload', [
            'tour_id' => $tour->id,
            'media_id' => $media->id,
            'cleared' => $cleared,
            'path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'media_id' => $media->id,
            'path' => $path,
            'cleared' => $cleared,
        ]);
    }
}
