<?php

namespace App\Jobs;

use App\Models\Tour;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class GenerateTourSlug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tourId;
    protected int $languageId;
    protected string $title;

    public $tries = 3;

    public function __construct(int $tourId, int $languageId, string $title)
    {
        $this->tourId = $tourId;
        $this->languageId = $languageId;
        $this->title = $title;
        $this->onQueue('seo');
    }

    public function handle(): void
    {
        try {
            $slug = Str::slug($this->title);
            $originalSlug = $slug;
            $counter = 1;

            while (DB::table('tour_translations')->where('slug', $slug)->exists()) {
                $translation = DB::table('tour_translations')->where('slug', $slug)->first();
                if ($translation && $translation->tour_id == $this->tourId) {
                    break;
                }
                $slug = $originalSlug . '-' . $counter++;
            }

            $updated = DB::table('tour_translations')
                ->where('tour_id', $this->tourId)
                ->where('language_id', $this->languageId)
                ->update(['slug' => $slug]);

            if ($updated) {
                Log::info("Slug generated successfully", [
                    'tour_id' => $this->tourId,
                    'language_id' => $this->languageId,
                    'slug' => $slug
                ]);
            }
        } catch (Exception $e) {
            Log::error("Error generating tour slug", [
                'tour_id' => $this->tourId,
                'language_id' => $this->languageId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error("Failed to generate tour slug", [
            'tour_id' => $this->tourId,
            'language_id' => $this->languageId,
            'error' => $exception->getMessage()
        ]);
    }
}