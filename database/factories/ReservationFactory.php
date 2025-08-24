<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Reservation;

class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'start_date' => fake()->dateTime(),
            'end_date' => fake()->dateTime(),
            'note' => fake()->word(),
            'is_active' => fake()->word(),
            'is_paid' => fake()->word(),
        ];
    }
}
