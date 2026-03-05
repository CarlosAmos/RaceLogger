<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Track;
use App\Models\Country;

class ImportF1Circuits extends Command
{
    protected $signature = 'import:f1-circuits';
    protected $description = 'Import circuits from Ergast dataset';

    public function handle()
    {
        $path = storage_path('app/f1_import/circuits.csv');

        if (!file_exists($path)) {
            $this->error('circuits.csv not found.');
            return;
        }

        $handle = fopen($path, 'r');

        // Get header row
        $header = fgetcsv($handle, 0, ",");

        while (($row = fgetcsv($handle, 0, ",")) !== false) {

            $data = array_combine($header, $row);

            if (!isset($data['name'])) {
                $this->warn('Skipping malformed row');
                $this->warn(json_encode($data));
                continue;
            }

            $name = trim($data['name']);
            $city = trim($data['location']);
            $countryName = trim($data['country']);

            $country = Country::where('name', $countryName)->first();

            if (!$country) {
                $this->warn("Country not found: {$countryName}");
                continue;
            }

            // Check existing circuit
            $existing = Track::where('name', $name)
                ->orWhere(function ($query) use ($city, $country) {
                    $query->where('city', $city)
                          ->where('country_id', $country->id);
                })
                ->first();

            if ($existing) {
                $this->line("Skipping existing circuit: {$name}");
                continue;
            }

            // Generate short name
            $shortName = str_replace([
                'Grand Prix Circuit',
                'International Circuit',
                'Motor Speedway',
                'Circuit'
            ], '', $name);

            $shortName = trim($shortName);

            Track::create([
                'name' => $name,
                'name_short' => $shortName ?: $name,
                'city' => $city,
                'country_id' => $country->id
            ]);

            $this->info("Added circuit: {$name}");
        }

        fclose($handle);

        $this->info('Circuit import completed.');
    }
}