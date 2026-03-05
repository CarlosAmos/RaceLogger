<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver;
use App\Models\Country;

class ImportF1Drivers extends Command
{
    protected $signature = 'import:f1-drivers';
    protected $description = 'Import drivers from Ergast dataset';

    public function handle()
    {
        $path = storage_path('app/f1_import/drivers.csv');

        if (!file_exists($path)) {
            $this->error('drivers.csv not found.');
            return;
        }

        $nationalityMap = [

            'American' => 'United States',
            'Argentine' => 'Argentina',
            'Argentine-Italian' => 'Argentina',
            'Australian' => 'Australia',
            'Austrian' => 'Austria',
            'Belgian' => 'Belgium',
            'Brazilian' => 'Brazil',
            'British' => 'United Kingdom',
            'Canadian' => 'Canada',
            'Chilean' => 'Chile',
            'Chinese' => 'China',
            'Colombian' => 'Colombia',
            'Czech' => 'Czech Republic',
            'Danish' => 'Denmark',
            'Dutch' => 'Netherlands',
            'East German' => 'Germany',
            'Finnish' => 'Finland',
            'French' => 'France',
            'German' => 'Germany',
            'Hungarian' => 'Hungary',
            'Indian' => 'India',
            'Indonesian' => 'Indonesia',
            'Irish' => 'Ireland',
            'Italian' => 'Italy',
            'Japanese' => 'Japan',
            'Liechtensteiner' => 'Liechtenstein',
            'Malaysian' => 'Malaysia',
            'Mexican' => 'Mexico',
            'Monegasque' => 'Monaco',
            'Moroccan' => 'Morocco',
            'New Zealander' => 'New Zealand',
            'Polish' => 'Poland',
            'Portuguese' => 'Portugal',
            'Rhodesian' => 'Zimbabwe',
            'Russian' => 'Russia',
            'South African' => 'South Africa',
            'South Korean' => 'South Korea',
            'Spanish' => 'Spain',
            'Swedish' => 'Sweden',
            'Swiss' => 'Switzerland',
            'Thai' => 'Thailand',
            'Turkish' => 'Turkey',
            'Uruguayan' => 'Uruguay',
            'Venezuelan' => 'Venezuela',
        ];

        $handle = fopen($path, 'r');

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            $firstName = trim($data['forename']);
            $lastName = trim($data['surname']);
            $nationality = trim($data['nationality']);
            $dob = $data['dob'];

            $countryName = $nationalityMap[$nationality] ?? $nationality;

            $country = Country::where('name', $countryName)->first();

            if (!$country) {
                $this->warn("Country not found for nationality: {$nationality}");
                continue;
            }

            $existing = Driver::where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->first();

            if ($existing) {
                $this->line("Skipping existing driver: {$firstName} {$lastName}");
                continue;
            }

            Driver::create([
                'world_id' => 1,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country_id' => $country->id,
                'date_of_birth' => $dob ?: null
            ]);

            $this->info("Added driver: {$firstName} {$lastName}");
        }

        fclose($handle);

        $this->info('Driver import completed.');
    }
}
