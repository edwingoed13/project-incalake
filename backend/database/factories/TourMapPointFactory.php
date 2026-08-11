<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourMapPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TourMapPoint>
 */
class TourMapPointFactory extends Factory
{
    protected $model = TourMapPoint::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'name' => $this->faker->streetName(),
            // Stored as a single "lat,lng" string, not two columns.
            'coordinates' => sprintf(
                '%.6f,%.6f',
                $this->faker->latitude(-16.5, -15.5),
                $this->faker->longitude(-70.5, -69.5)
            ),
            'type' => 'lugar_turistico',
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
