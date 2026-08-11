<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCode;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 *
 * Products hang off a Service (which hangs off a ServiceCode and a Language)
 * and a ProductCode. The whole chain is built here so a test that only cares
 * about "some product exists" doesn't have to know any of it.
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->sentence(5),
            'code' => strtoupper($this->faker->unique()->bothify('PF####')),
            'service_id' => Service::factory(),
            'product_code_id' => ProductCode::factory(),
            // NOT NULL with no default: 1 = ask passenger data before purchase.
            'data_requirement' => 1,
        ];
    }
}
