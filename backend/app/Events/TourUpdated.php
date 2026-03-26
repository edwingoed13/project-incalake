<?php

namespace App\Events;

use App\Models\Tour;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TourUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tour $tour;
    public array $changedFields;

    public function __construct(Tour $tour, array $changedFields = [])
    {
        $this->tour = $tour;
        $this->changedFields = $changedFields;
    }
}