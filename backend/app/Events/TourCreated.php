<?php

namespace App\Events;

use App\Models\Tour;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TourCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tour $tour;
    public array $tempImages;

    public function __construct(Tour $tour, array $tempImages = [])
    {
        $this->tour = $tour;
        $this->tempImages = $tempImages;
    }
}