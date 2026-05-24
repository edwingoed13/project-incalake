<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class TourMediaService
{
    public function processImages(Tour $tour, array $mediaData): void
    {
        try {
            $finalDir = 'tours/' . $tour->id;

            foreach ($mediaData as $index => $item) {
                if (!isset($item['path']) || !Storage::disk('public')->exists($item['path'])) {
                    continue;
                }

                // Move the displayed (possibly cropped) file out of temp.
                $displayPath = str_replace('tours/temp', $finalDir, $item['path']);
                Storage::disk('public')->move($item['path'], $displayPath);

                // Move the original too (kept for non-destructive re-editing).
                // When the image was never cropped, original == display.
                $originalTemp = $item['original_path'] ?? $item['path'];
                if ($originalTemp === $item['path']) {
                    $originalPath = $displayPath;
                } elseif (Storage::disk('public')->exists($originalTemp)) {
                    $originalPath = str_replace('tours/temp', $finalDir, $originalTemp);
                    Storage::disk('public')->move($originalTemp, $originalPath);
                } else {
                    $originalPath = $displayPath;
                }

                $tour->mediaGallery()->create([
                    'language_id' => $tour->primary_language_id,
                    'image_path' => $displayPath,
                    'original_path' => $originalPath,
                    'crop_data' => $item['crop_data'] ?? null,
                    'alt_text' => $item['alt_text'] ?? ($tour->code . ' - Image ' . ($index + 1)),
                    'title_text' => $item['title_text'] ?? ($tour->translations->first()->h1_title ?? 'Tour Image'),
                    'description' => $item['description'] ?? null,
                    'is_primary' => $item['is_primary'] ?? false,
                    'order' => $item['order'] ?? ($index + 1)
                ]);
            }

            Log::info("Images processed successfully", ['tour_id' => $tour->id, 'count' => count($mediaData)]);
        } catch (Exception $e) {
            Log::error("Error processing images", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncMediaGallery(Tour $tour, array $mediaGallery): void
    {
        try {
            $existingIds = [];
            
            $finalDir = 'tours/' . $tour->id;

            foreach ($mediaGallery as $index => $item) {
                if (isset($item['id']) && !str_contains((string) $item['id'], '-')) { // Numeric ID means existing record
                    $media = $tour->mediaGallery()->find($item['id']);
                    if ($media) {
                        $updates = [
                            'alt_text' => $item['alt_text'] ?? '',
                            'title_text' => $item['title_text'] ?? '',
                            'description' => $item['description'] ?? '',
                            'is_primary' => $item['is_primary'] ?? false,
                            'order' => $item['order'] ?? $index,
                        ];

                        if (array_key_exists('crop_data', $item)) {
                            $updates['crop_data'] = $item['crop_data']; // array | null
                        }

                        // The editor re-cropped this image: a freshly derived file
                        // was uploaded to tours/temp. Swap it in as the displayed
                        // image while preserving the original for future edits.
                        $newTemp = $item['new_display_path'] ?? null;
                        if ($newTemp && Storage::disk('public')->exists($newTemp)) {
                            $newDisplay = str_replace('tours/temp', $finalDir, $newTemp);
                            Storage::disk('public')->move($newTemp, $newDisplay);

                            if (empty($media->original_path)) {
                                // First crop of a legacy image: keep the current
                                // file as the original (do not delete it).
                                $updates['original_path'] = $media->image_path;
                            } elseif ($media->image_path
                                && $media->image_path !== $media->original_path
                                && Storage::disk('public')->exists($media->image_path)) {
                                // Superseded derived crop — safe to remove.
                                Storage::disk('public')->delete($media->image_path);
                            }

                            $updates['image_path'] = $newDisplay;
                        }

                        $media->update($updates);
                        $existingIds[] = $media->id;
                    }
                }
            }

            // Delete images removed in the editor (no longer present in the
            // payload). The frontend always sends the full kept list, so any
            // existing record whose id isn't in $existingIds was deleted by the
            // user. Runs before processImages() adds new temp uploads, so it
            // only targets the dropped existing records. Also remove the file.
            $toDelete = $tour->mediaGallery()->whereNotIn('id', $existingIds)->get();
            foreach ($toDelete as $media) {
                foreach (array_unique(array_filter([$media->image_path, $media->original_path])) as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
                $media->delete();
            }

            Log::info("Media gallery synced successfully", [
                'tour_id' => $tour->id,
                'kept' => count($existingIds),
                'deleted' => $toDelete->count(),
            ]);
        } catch (Exception $e) {
            Log::error("Error syncing media gallery", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteImage(Tour $tour, int $mediaId): bool
    {
        try {
            $media = $tour->mediaGallery()->findOrFail($mediaId);

            if (Storage::disk('public')->exists($media->image_path)) {
                Storage::disk('public')->delete($media->image_path);
            }

            $media->delete();

            Log::info("Image deleted successfully", ['tour_id' => $tour->id, 'media_id' => $mediaId]);
            return true;
        } catch (Exception $e) {
            Log::error("Error deleting image", ['tour_id' => $tour->id, 'media_id' => $mediaId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function reorderImages(Tour $tour, array $imageIds): void
    {
        try {
            foreach ($imageIds as $index => $mediaId) {
                $tour->mediaGallery()->where('id', $mediaId)->update(['order' => $index + 1]);
            }

            Log::info("Images reordered successfully", ['tour_id' => $tour->id]);
        } catch (Exception $e) {
            Log::error("Error reordering images", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteTempImage(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return true;
        } catch (Exception $e) {
            Log::error("Error deleting temp image", ['path' => $path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function validateImageFile($file): array
    {
        $errors = [];
        $maxSize = 5 * 1024 * 1024;
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        if ($file->getSize() > $maxSize) {
            $errors[] = 'El tamaño de la imagen no debe exceder 5MB';
        }

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'El formato de imagen debe ser JPG, PNG o WebP';
        }

        return $errors;
    }
}