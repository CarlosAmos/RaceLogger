<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [

            // 🇪🇺 Europe
            ['name' => 'Belgium', 'iso_code' => 'BEL', 'continent' => 'Europe'],
            ['name' => 'France', 'iso_code' => 'FRA', 'continent' => 'Europe'],
            ['name' => 'Germany', 'iso_code' => 'DEU', 'continent' => 'Europe'],
            ['name' => 'Italy', 'iso_code' => 'ITA', 'continent' => 'Europe'],
            ['name' => 'United Kingdom', 'iso_code' => 'GBR', 'continent' => 'Europe'],
            ['name' => 'Spain', 'iso_code' => 'ESP', 'continent' => 'Europe'],
            ['name' => 'Portugal', 'iso_code' => 'PRT', 'continent' => 'Europe'],
            ['name' => 'Netherlands', 'iso_code' => 'NLD', 'continent' => 'Europe'],
            ['name' => 'Austria', 'iso_code' => 'AUT', 'continent' => 'Europe'],
            ['name' => 'Hungary', 'iso_code' => 'HUN', 'continent' => 'Europe'],
            ['name' => 'Monaco', 'iso_code' => 'MCO', 'continent' => 'Europe'],
            ['name' => 'Switzerland', 'iso_code' => 'CHE', 'continent' => 'Europe'],
            ['name' => 'Denmark', 'iso_code' => 'DNK', 'continent' => 'Europe'],
            ['name' => 'Sweden', 'iso_code' => 'SWE', 'continent' => 'Europe'],

            // 🇺🇸 North America
            ['name' => 'United States', 'iso_code' => 'USA', 'continent' => 'North America'],
            ['name' => 'Canada', 'iso_code' => 'CAN', 'continent' => 'North America'],
            ['name' => 'Mexico', 'iso_code' => 'MEX', 'continent' => 'North America'],

            // 🇧🇷 South America
            ['name' => 'Brazil', 'iso_code' => 'BRA', 'continent' => 'South America'],
            ['name' => 'Argentina', 'iso_code' => 'ARG', 'continent' => 'South America'],

            // 🇯🇵 Asia
            ['name' => 'Japan', 'iso_code' => 'JPN', 'continent' => 'Asia'],
            ['name' => 'China', 'iso_code' => 'CHN', 'continent' => 'Asia'],
            ['name' => 'Singapore', 'iso_code' => 'SGP', 'continent' => 'Asia'],
            ['name' => 'Qatar', 'iso_code' => 'QAT', 'continent' => 'Asia'],
            ['name' => 'Bahrain', 'iso_code' => 'BHR', 'continent' => 'Asia'],
            ['name' => 'Saudi Arabia', 'iso_code' => 'SAU', 'continent' => 'Asia'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'ARE', 'continent' => 'Asia'],
            ['name' => 'Malaysia', 'iso_code' => 'MYS', 'continent' => 'Asia'],
            ['name' => 'South Korea', 'iso_code' => 'KOR', 'continent' => 'Asia'],

            // 🇦🇺 Oceania
            ['name' => 'Australia', 'iso_code' => 'AUS', 'continent' => 'Oceania'],
            ['name' => 'New Zealand', 'iso_code' => 'NZL', 'continent' => 'Oceania'],

            // 🇿🇦 Africa
            ['name' => 'South Africa', 'iso_code' => 'ZAF', 'continent' => 'Africa'],
            ['name' => 'Morocco', 'iso_code' => 'MAR', 'continent' => 'Africa'],

            // 🇹🇭 Southeast Asia (future-proofing)
            ['name' => 'Thailand', 'iso_code' => 'THA', 'continent' => 'Asia'],

        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['name' => $country['name']],
                $country
            );
        }
    }
}
