<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = ['台北市', '新北市', '桃園市', '台中市', '台南市', '高雄市', '新竹市'];
        $districts = ['中正區', '大安區', '信義區', '板橋區', '中壢區', '西屯區', '東區', '左營區'];
        $roads = ['中山路', '中正路', '民生路', '仁愛街', '和平街', '信義路', '光復路'];
        $surnames = ['陳', '林', '黃', '張', '李', '王', '吳', '劉', '蔡', '楊'];
        $nameChars = ['怡', '庭', '雅', '婷', '志', '明', '俊', '豪', '嘉', '瑋', '家', '宇', '欣', '妍', '承', '翰'];

        $city = $this->faker->randomElement($cities);
        $district = $this->faker->randomElement($districts);
        $road = $this->faker->randomElement($roads);
        $lane = $this->faker->numberBetween(1, 120);
        $alley = $this->faker->numberBetween(1, 80);
        $number = $this->faker->numberBetween(1, 500);
        $floor = $this->faker->numberBetween(1, 20);

        $hasLane = $this->faker->boolean();
        $lanePart = $hasLane ? $lane . '巷' : '';
        $alleyPart = $hasLane && $this->faker->boolean() ? $alley . '弄' : '';

        $nationalId = $this->faker->unique()->regexify('[A-Z][12][0-9]{8}');

        return [
            'first_name' => $this->faker->randomElement($nameChars) . $this->faker->randomElement($nameChars),
            'last_name' => $this->faker->randomElement($surnames),
            'national_id_number' => $nationalId,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->regexify('09[0-9]{8}'),
            'password' => Hash::make('password'),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'address' => $city . $district . $road . $lanePart . $alleyPart . $number . '號' . $floor . '樓',
            'gender' => $this->faker->numberBetween(1, 3),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
