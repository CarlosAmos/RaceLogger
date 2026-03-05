<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Season;
use App\Models\Track;
use App\Models\TrackLayout;
use App\Models\CalendarRace;

class ImportF1CalendarRaces extends Command
{
    protected $signature = 'import:f1-calendar {year}';
    protected $description = 'Import F1 calendar races';

    public function handle()
    {
        $year = $this->argument('year');

        $racesPath = storage_path('app/f1_import/races.csv');
        $circuitsPath = storage_path('app/f1_import/circuits.csv');

        /*
        |--------------------------------
        | Build circuit map
        |--------------------------------
        */

        $circuitMap = [];

        $handle = fopen($circuitsPath, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            $circuitMap[$data['circuitId']] = $data['name'];
        }

        fclose($handle);

        /*
        |--------------------------------
        | Import races
        |--------------------------------
        */

        $handle = fopen($racesPath, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            if ($data['year'] != $year) {
                continue;
            }

            $season = Season::where('year', $year)->first();

            if (!$season) {
                $this->warn("Season not found {$year}");
                continue;
            }

            $circuitName = $circuitMap[$data['circuitId']] ?? null;

            if (!$circuitName) {
                $this->warn("Circuit not found for race {$data['name']}");
                continue;
            }

            $track = Track::where('name', 'LIKE', "%{$circuitName}%")
                ->orWhere('name_short', 'LIKE', "%{$circuitName}%")
                ->first();

            if (!$track) {
                $this->warn("Track not found: {$circuitName}");
                continue;
            }

            $layout = TrackLayout::where('track_id', $track->id)
                ->where('active_from', '<=', $year)
                ->where(function ($q) use ($year) {
                    $q->whereNull('active_to')
                      ->orWhere('active_to', '>=', $year);
                })
                ->first();

            if (!$layout) {
                $this->warn("Layout not found for {$circuitName} ({$year})");
                continue;
            }

            $exists = CalendarRace::where('season_id', $season->id)
                ->where('round_number', $data['round'])
                ->first();

            if ($exists) {
                $this->line("Race already exists: {$data['name']} ({$year})");
                continue;
            }

            CalendarRace::create([
                'season_id' => $season->id,
                'track_layout_id' => $layout->id,
                'round_number' => $data['round'],
                'gp_name' => $data['name'],
                'race_code' => strtoupper(substr($data['name'],0,3)),
                'race_date' => $data['date'],
                'is_locked' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("Added {$data['name']}");
        }

        fclose($handle);

        $this->info("Calendar import complete for {$year}");
    }
}