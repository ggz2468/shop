<?php

namespace Database\Seeders;

use App\Models\ProductSpec;
use Database\Factories\ProductSpecFactory;
use Illuminate\Database\Seeder;

class ProductSpecSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specStates = collect(ProductSpecFactory::colors())
            ->crossJoin(ProductSpecFactory::sizes())
            ->map(fn (array $spec): array => [
                'color' => $spec[0],
                'size' => $spec[1]
            ])
            ->all();

        ProductSpec::factory()
            ->count(count($specStates))
            ->sequence(...$specStates)
            ->create();
    }
}
