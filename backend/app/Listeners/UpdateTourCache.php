<?php

namespace App\Listeners;

use App\Events\TourUpdated;
use App\Jobs\UpdateTourSchemaMarkup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class UpdateTourCache
{
    public function handle(TourUpdated $event): void
    {
        try {
            Log::info("Updating tour cache", ['tour_id' => $event->tour->id]);

            cache()->forget("tour_{$event->tour->id}");

            if (in_array('translations', $event->changedFields) ||
                in_array('prices', $event->changedFields) ||
                in_array('active', $event->changedFields)) {

                $languages = ['ES', 'EN', 'FR'];
                $jobs = [];

                foreach ($languages as $lang) {
                    $jobs[] = new UpdateTourSchemaMarkup($event->tour->id, $lang);
                }

                if (!empty($jobs)) {
                    Bus::chain($jobs)->dispatch();
                }
            }

            Log::info("Tour cache updated successfully", ['tour_id' => $event->tour->id]);
        } catch (\Exception $e) {
            Log::error("Error updating tour cache", [
                'tour_id' => $event->tour->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}