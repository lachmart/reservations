<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->text(),
            'status' => fake()->word(),
            'category_id' => Category::factory(),
            'price_per_day' => fake()->numberBetween(1, 30) * 50,
            'stock' => fake()->numberBetween(1, 20),
        ];
    }
}
