<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedCount = 15;
        $imagePaths = ProductFactory::imagePaths();
        $catalog = array_values(array_map(
            fn (string $path): array => [
                'name' => pathinfo($path, PATHINFO_FILENAME),
                'url' => '/storage/' . $path,
            ],
            $imagePaths
        ));

        for ($index = 0; $index < $seedCount; $index++) {
            $item = Arr::get($catalog, $index, [
                'name' => ProductFactory::fallbackName(),
                'url' => ProductFactory::PLACEHOLDER_IMAGE_URL,
            ]);
            $productName = $item['name'];
            $product = Product::factory()->create([
                'name' => $productName,
                'description' => "這是 {$productName} 的描述。",
            ]);

            Image::factory()->create([
                'url' => $item['url'],
                'imageable_type' => Product::class,
                'imageable_id' => $product->id,
            ]);
        }
    }
}
