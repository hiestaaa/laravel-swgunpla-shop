<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'ward' => fake()->city(),
            'is_default' => fake()->boolean(30),
        ];
    }
}
