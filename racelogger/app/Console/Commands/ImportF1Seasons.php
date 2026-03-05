<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Season;

class ImportF1Seasons extends Command
{
    protected $signature = 'import:f1-seasons';
    protected $description = 'Import F1 seasons from Ergast dataset';

    public function handle()
    {
        $path = storage_path('app/f1_import/seasons.csv');

        if (!file_exists($path)) {
            $this->error('seasons.csv not found.');
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        foreach ($rows as $row) {

            $data = array_combine($header, $row);

            $year = (int) $data['year'];

            // Determine correct points system
            if ($year <= 1959) {
                $pointsSystem = 7;
            } elseif ($year == 1960) {
                $pointsSystem = 8;
            } elseif ($year <= 1990) {
                $pointsSystem = 9;
            } elseif ($year <= 2002) {
                $pointsSystem = 10;
            } elseif ($year <= 2009) {
                $pointsSystem = 11;
            } else {
                $pointsSystem = 6;
            }

            Season::updateOrCreate(
                [
                    'series_id' => 8,
                    'year' => $year
                ],
                [
                    'is_simulated' => 0,
                    'point_system_id' => $pointsSystem,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        $this->info('F1 seasons imported successfully.');
    }
}