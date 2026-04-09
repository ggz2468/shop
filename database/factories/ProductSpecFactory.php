<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductSpec>
 */
class ProductSpecFactory extends Factory
{
    /**
     * Available color definitions used by seeding and factory generation.
     *
     * @return array<int, string>
     */
    public static function colors(): array
    {
        return [
            '紅',
            '橙',
            '黃',
            '綠',
            '藍',
            '靛',
            '紫',
            '黑',
            '白',
            '灰'
        ];
    }

    /**
     * Available size definitions used by seeding and factory generation.
     *
     * @return array<int, int>
     */
    public static function sizes(): array
    {
        return [1, 2, 3, 4, 5];
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'color' => $this->faker->randomElement(self::colors()),
            'size' => $this->faker->randomElement(self::sizes()),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
