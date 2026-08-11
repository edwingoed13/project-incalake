<?php

namespace Database\Factories;

use App\Models\ServiceCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCode>
 */
class ServiceCodeFactory extends Factory
{
    protected $model = ServiceCode::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
            'user_id' => User::factory(),
        ];
    }
}
