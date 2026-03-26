<?php

namespace App\Jobs;

use App\Models\Tour;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ProcessTourImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tourId;
    protected array $tempImages;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(int $tourId, array $tempImages)
    {
        $this->tourId = $tourId;
        $this->tempImages = $tempImages;
        $this->onQueue('images');
    }

    public function handle(): void
    {
        try {
            $tour = Tour::find($this->tourId);

            if (!$tour) {
                Log::warning("Tour not found for image processing", ['tour_id' => $this->tourId]);
                return;
            }

            foreach ($this->tempImages as $index => $tempImage) {
                if (!isset($tempImage['path'])) {
                    continue;
                }

                $finalPath = 'tours/' . $tour->id;
                $newPath = str_replace('tours/temp', $finalPath, $tempImage['path']);

                if (Storage::disk('public')->exists($tempImage['path'])) {
                    Storage::disk('public')->move($tempImage['path'], $newPath);

                    $tour->mediaGallery()->create([
                        'language_id' => $tour->primary_language_id,
                        'image_path' => $newPath,
                        'alt_text' => $tour->code . ' - Image ' . ($index + 1),
                        'title_text' => $tour->translations->first()->h1_title ?? 'Tour Image',
                        'order' => $index + 1
                    ]);

                    Log::info("Image processed successfully", [
                        'tour_id' => $tour->id,
                        'image_path' => $newPath
                    ]);
                }
            }

            Log::info("All images processed successfully for tour", ['tour_id' => $tour->id]);
        } catch (Exception $e) {
            Log::error("Error processing tour images", [
                'tour_id' => $this->tourId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Failed to process tour images", [
            'tour_id' => $this->tourId,
            'error' => $exception->getMessage()
        ]);
    }
}