<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Constructor;
use App\Models\Country;

class ImportF1Constructors extends Command
{
    protected $signature = 'import:f1-constructors';
    protected $description = 'Import F1 constructors from Ergast dataset';

    public function handle()
    {
        $path = storage_path('app/f1_import/constructors.csv');

        if (!file_exists($path)) {
            $this->error('constructors.csv not found.');
            return;
        }

        $nationalityMap = [

            'American' => 'United States',
            'Argentine' => 'Argentina',
            'Australian' => 'Australia',
            'Austrian' => 'Austria',
            'Belgian' => 'Belgium',
            'Brazilian' => 'Brazil',
            'British' => 'United Kingdom',
            'Botswana' => 'Botswana',
            'Canadian' => 'Canada',
            'Chinese' => 'China',
            'Danish' => 'Denmark',
            'Dutch' => 'Netherlands',
            'Finnish' => 'Finland',
            'French' => 'France',
            'German' => 'Germany',
            'Hungarian' => 'Hungary',
            'Indian' => 'India',
            'Irish' => 'Ireland',
            'Italian' => 'Italy',
            'Japanese' => 'Japan',
            'Malaysian' => 'Malaysia',
            'Mexican' => 'Mexico',
            'Monegasque' => 'Monaco',
            'New Zealander' => 'New Zealand',
            'Polish' => 'Poland',
            'Portuguese' => 'Portugal',
            'Russian' => 'Russia',
            'South African' => 'South Africa',
            'Spanish' => 'Spain',
            'Swedish' => 'Sweden',
            'Swiss' => 'Switzerland',
            'Thai' => 'Thailand',
            'Turkish' => 'Turkey'
        ];

        $handle = fopen($path, 'r');

        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            $name = trim($data['name']);
            $nationality = trim($data['nationality']);

            $countryName = $nationalityMap[$nationality] ?? $nationality;

            $country = Country::where('name', $countryName)->first();

            if (!$country) {
                $this->warn("Country not found: {$nationality}. id: ".trim($data['constructorId'])." Constructor: {$name}");
                continue;
            }

            $existing = Constructor::where('name', $name)->first();

            if ($existing) {
                //$this->line("Skipping existing constructor: {$name}");
                continue;
            }

            Constructor::create([
                'world_id' => 1,
                'name' => $name,
                'country_id' => $country->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("Added constructor: {$name}");
        }

        fclose($handle);

        $this->info('Constructor import completed.');
    }
}