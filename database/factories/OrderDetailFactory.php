<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderDetail>
 */
class OrderDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productVariant = ProductVariant::factory()->create();
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            ...$this->snapshot($productVariant, $quantity),
        ];
    }

    public function forProductVariant(ProductVariant $productVariant, ?int $quantity = null): static
    {
        return $this->state(fn (): array => $this->snapshot(
            $productVariant,
            $quantity ?? $this->faker->numberBetween(1, 5)
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ProductVariant $productVariant, int $quantity): array
    {
        return [
            'product_variant_id' => $productVariant->id,
            'product_name' => $productVariant->product->name,
            'product_sku' => $productVariant->sku,
            'product_color' => $productVariant->productSpec->color,
            'product_size' => $productVariant->productSpec->size,
            'product_price' => $productVariant->price,
            'quantity' => $quantity,
            'subtotal' => $productVariant->price * $quantity,
        ];
    }
}
