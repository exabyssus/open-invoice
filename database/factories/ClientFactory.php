<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'title' => fake()->company(),
            'registration_number' => fake()->numerify('400########'),
            'address' => fake()->address(),
        ];
    }
}
