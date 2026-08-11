<?php

namespace Database\Factories;

use App\Models\AgeStage;
use App\Models\Tour;
use App\Models\TourPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TourPrice>
 */
class TourPriceFactory extends Factory
{
    protected $model = TourPrice::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'age_stage_id' => fn () => $this->ageStage()->id,
            'amount' => $this->faker->randomFloat(2, 10, 500),
        ];
    }

    /** Age stages are a small fixed catalogue — reused, not multiplied. */
    private function ageStage(): AgeStage
    {
        return AgeStage::firstOrCreate(
            ['description' => 'Adulto'],
            ['min_age' => 18, 'max_age' => 99, 'editable' => false]
        );
    }
}
