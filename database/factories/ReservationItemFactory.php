<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Reservation;
use App\Models\ReservationItem;

class ReservationItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReservationItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(-10000, 10000),
            'reservation_id' => Reservation::factory(),
        ];
    }
}
