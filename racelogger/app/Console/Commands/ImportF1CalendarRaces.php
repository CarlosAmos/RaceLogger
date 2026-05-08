<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Season;
use App\Models\Track;
use App\Models\TrackLayout;
use App\Models\CalendarRace;
use Carbon\Carbon;

class ImportF1CalendarRaces extends Command
{
    protected $signature = 'import:f1-calendar {year} {--layout-overrides= : JSON map of ergast raceId → DB trackLayoutId for unmatched circuits}';
    protected $description = 'Import F1 calendar races';

    public function handle(): int
    {
        $year            = (int) $this->argument('year');
        $layoutOverrides = json_decode($this->option('layout-overrides') ?? '{}', true) ?? [];

        $racesPath    = storage_path('app/f1_import/races.csv');
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

        $season = Season::where('year', $year)->first();
        if (!$season) {
            $this->error("Season {$year} not found. Run import:f1-seasons first.");
            fclose($handle);
            return 1;
        }

        $added   = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {

            $data = array_combine($header, $row);

            if ((int) $data['year'] !== $year) {
                continue;
            }

            $raceId      = (int) $data['raceId'];
            $circuitName = $circuitMap[$data['circuitId']] ?? null;

            if (!$circuitName) {
                $this->warn("Circuit not found for race {$data['name']}");
                $skipped++;
                continue;
            }

            // Try to resolve the layout via name matching first, then fall back to manual override
            $layout = null;

            $track = Track::where('name', 'LIKE', "%{$circuitName}%")
                ->orWhere('name_short', 'LIKE', "%{$circuitName}%")
                ->first();

            if ($track) {
                $layout = TrackLayout::where('track_id', $track->id)
                    ->where('active_from', '<=', $year)
                    ->where(function ($q) use ($year) {
                        $q->whereNull('active_to')
                          ->orWhere('active_to', '>=', $year);
                    })
                    ->first();
            }

            // Fall back to manually-assigned layout override
            if (!$layout && isset($layoutOverrides[$raceId])) {
                $layout = TrackLayout::find((int) $layoutOverrides[$raceId]);
            }

            if (!$layout) {
                $this->warn("Layout not found for {$circuitName} ({$year}) — assign it on the import page and re-run");
                $skipped++;
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
                'season_id'       => $season->id,
                'track_layout_id' => $layout->id,
                'round_number'    => $data['round'],
                'gp_name'         => $data['name'],
                'race_code'       => strtoupper(substr($data['name'], 0, 3)),
                'race_date'       => $this->parseDate($data['date']),
                'is_locked'       => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $this->info("Added {$data['name']}");
            $added++;
        }

        fclose($handle);

        $this->info("Calendar import complete for {$year}: {$added} added, {$skipped} skipped.");
        return 0;
    }

    /**
     * Parse an Ergast date string to Y-m-d format.
     * Handles both YYYY-MM-DD and DD/MM/YYYY (older seasons).
     */
    private function parseDate(string $value): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // DD/MM/YYYY → swap to YYYY-MM-DD via Carbon
        return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
    }
}