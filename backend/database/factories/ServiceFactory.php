<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Service;
use App\Models\ServiceCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(3);

        return [
            'url' => "/{$slug}",
            'uri' => $slug,
            'page_title' => $this->faker->sentence(4),
            'page_description' => $this->faker->sentence(10),
            'language_id' => fn () => Language::firstOrCreate(['code' => 'ES'], ['country' => 'Español'])->id,
            'service_code_id' => ServiceCode::factory(),
        ];
    }
}
