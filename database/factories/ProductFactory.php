<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'product_number' => fake()->numerify('PRD-###'),
            'price' => fake()->numberBetween(1000, 50000),
            'default_quantity' => 1,
            'unit' => 'UNIT',
        ];
    }
}
