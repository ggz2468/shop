<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = array_values(array_unique(ProductFactory::names()));

        $products = Product::factory()
            ->count(count($names))
            ->sequence(fn ($sequence) => [
                'product_spec_id' => 1,
                'name' => $names[$sequence->index],
                'description' => '這是 ' . $names[$sequence->index] . ' 的描述。'
            ])
            ->create();

        $products->each(function (Product $product) {
            Image::factory()->create([
                'url' => $product->name . '.gif',
                'imageable_id' => $product->id,
                'imageable_type' => Product::class
            ]);
        });
    }
}
