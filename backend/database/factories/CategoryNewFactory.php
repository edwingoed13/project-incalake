<?php

namespace Database\Factories;

use App\Models\CategoryNew;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoryNew>
 *
 * categories_new carries no name column — the display text lives in
 * category_translations. `code` is the only thing required here.
 */
class CategoryNewFactory extends Factory
{
    protected $model = CategoryNew::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
        ];
    }
}
