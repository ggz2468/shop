<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fn () => Product::query()->create(Product::factory()->raw())->id,
            'product_spec_id' => ProductSpec::factory(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-########-????')),
            'price' => $this->faker->numberBetween(100, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}
