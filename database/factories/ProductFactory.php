<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductSpec;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * 產品預設圖片 URL
     * 
     * @var string
     */
    public const string PLACEHOLDER_IMAGE_URL = '/images/products/default.svg';

    /**
     * @return array<int, string>
     */
    public static function imagePaths(): array
    {
        return array_values(array_filter(
            Storage::disk('public')->files('images/products'),
            function (string $path): bool {
                $fileName = pathinfo($path, PATHINFO_FILENAME);
                return $fileName !== 'default' && !str_ends_with($fileName, 'small');
            }
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        $imagePaths = self::imagePaths();

        return array_values(array_map(
            fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $imagePaths
        ));
    }

    /**
     * Generate a random product name using faker.
     */
    public static function fallbackName(): string
    {
        $faker = fake();
        return ucfirst($faker->words($faker->numberBetween(2, 4), true));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_spec_id' => ProductSpec::factory(),
            'name' => self::fallbackName(),
            'price' => $this->faker->numberBetween(100, 1000),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
