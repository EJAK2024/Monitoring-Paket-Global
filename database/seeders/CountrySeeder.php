<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Germany', 'iso_code' => 'DE', 'iso_code_3' => 'DEU', 'currency_code' => 'EUR', 'region' => 'Europe', 'language' => 'German', 'gdp' => 4450, 'inflation' => 2.3, 'population' => 83200000, 'exports' => 1680, 'imports' => 1460],
            ['name' => 'China', 'iso_code' => 'CN', 'iso_code_3' => 'CHN', 'currency_code' => 'CNY', 'region' => 'Asia', 'language' => 'Chinese', 'gdp' => 17700, 'inflation' => 0.5, 'population' => 1412000000, 'exports' => 3590, 'imports' => 2680],
            ['name' => 'Indonesia', 'iso_code' => 'ID', 'iso_code_3' => 'IDN', 'currency_code' => 'IDR', 'region' => 'Asia', 'language' => 'Indonesian', 'gdp' => 1370, 'inflation' => 2.8, 'population' => 278000000, 'exports' => 292, 'imports' => 278],
            ['name' => 'Australia', 'iso_code' => 'AU', 'iso_code_3' => 'AUS', 'currency_code' => 'AUD', 'region' => 'Oceania', 'language' => 'English', 'gdp' => 1720, 'inflation' => 3.4, 'population' => 26400000, 'exports' => 412, 'imports' => 300],
            ['name' => 'United States', 'iso_code' => 'US', 'iso_code_3' => 'USA', 'currency_code' => 'USD', 'region' => 'Americas', 'language' => 'English', 'gdp' => 27360, 'inflation' => 2.9, 'population' => 335000000, 'exports' => 2020, 'imports' => 3170],
            ['name' => 'Japan', 'iso_code' => 'JP', 'iso_code_3' => 'JPN', 'currency_code' => 'JPY', 'region' => 'Asia', 'language' => 'Japanese', 'gdp' => 4230, 'inflation' => 2.5, 'population' => 124000000, 'exports' => 748, 'imports' => 780],
            ['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code_3' => 'SGP', 'currency_code' => 'SGD', 'region' => 'Asia', 'language' => 'English', 'gdp' => 501, 'inflation' => 3.1, 'population' => 5640000, 'exports' => 700, 'imports' => 580],
            ['name' => 'Malaysia', 'iso_code' => 'MY', 'iso_code_3' => 'MYS', 'currency_code' => 'MYR', 'region' => 'Asia', 'language' => 'Malay', 'gdp' => 430, 'inflation' => 2.0, 'population' => 33400000, 'exports' => 312, 'imports' => 274],
            ['name' => 'United Kingdom', 'iso_code' => 'GB', 'iso_code_3' => 'GBR', 'currency_code' => 'GBP', 'region' => 'Europe', 'language' => 'English', 'gdp' => 3340, 'inflation' => 2.2, 'population' => 67600000, 'exports' => 970, 'imports' => 1010],
            ['name' => 'India', 'iso_code' => 'IN', 'iso_code_3' => 'IND', 'currency_code' => 'INR', 'region' => 'Asia', 'language' => 'Hindi', 'gdp' => 3550, 'inflation' => 4.8, 'population' => 1428000000, 'exports' => 770, 'imports' => 830],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}
