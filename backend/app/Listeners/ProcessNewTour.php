<?php

namespace App\Listeners;

use App\Events\TourCreated;
use App\Jobs\ProcessTourImages;
use App\Jobs\UpdateTourSchemaMarkup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class ProcessNewTour
{
    public function handle(TourCreated $event): void
    {
        try {
            Log::info("Processing new tour", ['tour_id' => $event->tour->id]);

            $jobs = [];

            if (!empty($event->tempImages)) {
                $jobs[] = new ProcessTourImages($event->tour->id, $event->tempImages);
            }

            $languages = ['ES', 'EN', 'FR'];
            foreach ($languages as $lang) {
                $jobs[] = new UpdateTourSchemaMarkup($event->tour->id, $lang);
            }

            if (!empty($jobs)) {
                Bus::chain($jobs)->dispatch();
            }

            Log::info("New tour processed successfully", ['tour_id' => $event->tour->id]);
        } catch (\Exception $e) {
            Log::error("Error processing new tour", [
                'tour_id' => $event->tour->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}