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
            foreach ($mediaData as $index => $item) {
                if (!isset($item['path'])) {
                    continue;
                }

                $finalPath = 'tours/' . $tour->id;
                $newPath = str_replace('tours/temp', $finalPath, $item['path']);

                if (Storage::disk('public')->exists($item['path'])) {
                    Storage::disk('public')->move($item['path'], $newPath);

                    $tour->mediaGallery()->create([
                        'language_id' => $tour->primary_language_id,
                        'image_path' => $newPath,
                        'alt_text' => $item['alt_text'] ?? ($tour->code . ' - Image ' . ($index + 1)),
                        'title_text' => $item['title_text'] ?? ($tour->translations->first()->h1_title ?? 'Tour Image'),
                        'description' => $item['description'] ?? null,
                        'is_primary' => $item['is_primary'] ?? false,
                        'order' => $item['order'] ?? ($index + 1)
                    ]);
                }
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
            
            foreach ($mediaGallery as $index => $item) {
                if (isset($item['id']) && !str_contains($item['id'], '-')) { // Numeric ID means existing record
                    $media = $tour->mediaGallery()->find($item['id']);
                    if ($media) {
                        $media->update([
                            'alt_text' => $item['alt_text'] ?? '',
                            'title_text' => $item['title_text'] ?? '',
                            'description' => $item['description'] ?? '',
                            'is_primary' => $item['is_primary'] ?? false,
                            'order' => $item['order'] ?? $index,
                        ]);
                        $existingIds[] = $media->id;
                    }
                }
            }

            // Optional: Delete images that are no longer in the gallery
            // $tour->mediaGallery()->whereNotIn('id', $existingIds)->delete();

            Log::info("Media gallery synced successfully", ['tour_id' => $tour->id]);
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