<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductSpec;
use Database\Factories\ProductFactory;
use Illuminate\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    /**
     * Run the database seeds for performance testing.
     */
    public function run(): void
    {
        $productCount = max(1, (int) env('PERF_PRODUCTS_COUNT', 3000));
        $memberCount = max(1, (int) env('PERF_MEMBERS_COUNT', 500));
        $batchSize = max(1, (int) env('PERF_SEED_BATCH_SIZE', 300));

        $this->call(ProductSpecSeeder::class);

        $specIds = ProductSpec::query()->pluck('id')->all();
        $names = array_values(array_unique(ProductFactory::names()));

        if (empty($names)) {
            $names = ['壓測商品'];
        }

        for ($offset = 0; $offset < $productCount; $offset += $batchSize) {
            $currentBatchSize = min($batchSize, $productCount - $offset);

            $products = Product::factory()
                ->count($currentBatchSize)
                ->sequence(function ($sequence) use ($offset, $names, $specIds): array {
                    $index = $offset + $sequence->index;
                    $baseName = $names[$index % count($names)];

                    return [
                        'product_spec_id' => $specIds[$index % count($specIds)],
                        'name' => sprintf('%s 壓測#%d', $baseName, $index + 1),
                        'description' => sprintf('壓力測試資料: %s (%d)', $baseName, $index + 1),
                    ];
                })
                ->create();

            $products->each(function (Product $product): void {
                Image::query()->create([
                    'url' => '/storage/images/products/default.png',
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                ]);
            });
        }

        Member::factory()->count($memberCount)->create();
    }
}
