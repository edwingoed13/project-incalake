<?php

namespace Database\Factories;

use App\Models\ProductCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCode>
 */
class ProductCodeFactory extends Factory
{
    protected $model = ProductCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
