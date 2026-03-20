<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Season;
use App\Models\Constructor;
use App\Models\Driver;
use App\Models\CalendarRace;
use App\Models\RaceSession;
use App\Models\EntryCar;
use App\Models\Result;
use App\Models\ResultDriver;
use App\Models\QualifyingSession;
use App\Models\QualifyingResult;
use App\Services\LapRecordService;

class ImportF1Results extends Command
{
    protected $signature = 'import:f1-results {year} {--series=8} {--world=1}';
    protected $description = 'Import F1 race and sprint results from Ergast dataset';

    public function handle(): int
    {
        $year     = (int) $this->argument('year');
        $seriesId = (int) $this->option('series');
        $worldId  = (int) $this->option('world');
        $base     = storage_path('app/f1_import');

        foreach (['drivers.csv', 'constructors.csv', 'races.csv', 'results.csv'] as $file) {
            if (!file_exists("{$base}/{$file}")) {
                $this->error("Missing: {$base}/{$file}");
                return 1;
            }
        }

        // ── 1. Season ────────────────────────────────────────────────────────
        $season = Season::where('year', $year)->where('series_id', $seriesId)->first();
        if (!$season) {
            $this->error("Season {$year} not found. Run import:f1-seasons first.");
            return 1;
        }

        // ── 2. Build lookup maps ──────────────────────────────────────────────
        $raceMap        = $this->buildRaceMap($year, $season->id, "{$base}/races.csv");
        $driverMap      = $this->buildDriverMap("{$base}/drivers.csv");
        $constructorMap = $this->buildConstructorMap("{$base}/constructors.csv", $worldId);
        $entryCarMap    = $this->buildEntryCarMap($season->id);

        $this->info("Races: "       . count($raceMap)        . " | " .
                    "Drivers: "     . count($driverMap)       . " | " .
                    "Constructors: ". count($constructorMap)  . " | " .
                    "Entry cars: "  . count($entryCarMap, COUNT_RECURSIVE) - count($entryCarMap));

        $lapRecords = new LapRecordService();

        // ── 3. Main race results ──────────────────────────────────────────────
        $this->info("\nImporting race results...");
        $this->processFile("{$base}/results.csv", $raceMap, $driverMap, $constructorMap, $entryCarMap, false, $lapRecords);

        // ── 4. Sprint results (optional) ──────────────────────────────────────
        $sprintPath = "{$base}/sprint_results.csv";
        if (file_exists($sprintPath)) {
            $this->info("\nImporting sprint results...");
            $this->processFile($sprintPath, $raceMap, $driverMap, $constructorMap, $entryCarMap, true, $lapRecords);
        }

        // ── 5. Qualifying (optional) ──────────────────────────────────────────
        $qualiPath = "{$base}/qualifying.csv";
        if (file_exists($qualiPath)) {
            $this->info("\nImporting qualifying results...");
            $this->processQualifying($qualiPath, $raceMap, $constructorMap, $entryCarMap);
        }

        // ── 6. Lock calendar races ────────────────────────────────────────────
        $locked = 0;
        foreach ($raceMap as $calRace) {
            if (!$calRace->is_locked) {
                $calRace->update(['is_locked' => true]);
                $locked++;
            }
        }

        $this->info("\nLocked {$locked} calendar race(s).");
        $this->info("Results import complete for {$year}.");
        return 0;
    }

    /**
     * Load, group, and persist results from a single CSV file.
     *
     * @param array<int, CalendarRace> $raceMap
     * @param array<int, int>          $driverMap       ergast driverId → DB driver id
     * @param array<int, Constructor>  $constructorMap  ergast constructorId → Constructor
     * @param array                    $entryCarMap     [db_constructor_id][car_number] → EntryCar
     */
    private function processFile(
        string           $path,
        array            $raceMap,
        array            $driverMap,
        array            $constructorMap,
        array            $entryCarMap,
        bool             $isSprint,
        LapRecordService $lapRecords
    ): void {
        $raceIdSet = array_flip(array_keys($raceMap));
        $rows      = [];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (isset($raceIdSet[(int) $data['raceId']])) {
                $rows[] = $data;
            }
        }

        fclose($handle);

        // Winner total time per race (ms) — used for gap calculation
        $winnerMs = [];
        foreach ($rows as $row) {
            if ($row['positionText'] === '1') {
                $ms = $this->nullish($row['milliseconds']);
                if ($ms) $winnerMs[(int) $row['raceId']] = (int) $ms;
            }
        }

        // Group by raceId + constructorId + car_number to handle shared drives
        $grouped = [];
        foreach ($rows as $row) {
            $key             = $row['raceId'] . '|' . $row['constructorId'] . '|' . $row['number'];
            $grouped[$key][] = $row;
        }

        $created = 0;
        $skipped = 0;

        foreach ($grouped as $rows) {
            // Best-placed row drives the result record
            usort($rows, fn($a, $b) => (int) $a['positionOrder'] <=> (int) $b['positionOrder']);
            $primary = $rows[0];

            $raceId            = (int) $primary['raceId'];
            $ergastConstructor = (int) $primary['constructorId'];
            $carNumber         = $primary['number'];

            // Resolve calendar race and session
            $calRace     = $raceMap[$raceId] ?? null;
            $constructor = $constructorMap[$ergastConstructor] ?? null;

            if (!$calRace || !$constructor) { $skipped++; continue; }

            $session = RaceSession::where('calendar_race_id', $calRace->id)
                ->where('is_sprint', $isSprint)
                ->first();

            if (!$session) {
                $this->warn("  No " . ($isSprint ? 'sprint ' : '') . "session for round {$calRace->round_number}");
                $skipped++;
                continue;
            }

            // Resolve EntryCar via DB constructor id
            $entryCar = $entryCarMap[$constructor->id][$carNumber] ?? null;
            if (!$entryCar) {
                $this->warn("  EntryCar not found: {$constructor->name} car #{$carNumber} round {$calRace->round_number}");
                $skipped++;
                continue;
            }

            // Resolve fastest lap time before the skip check so we can backfill if needed
            $fastestLapMs = $this->parseLapTime($primary['fastestLapTime'] ?? $primary['fastestlaptime'] ?? '\N');
            $isFastestLap = ($this->nullish($primary['rank'] ?? '\N') === '1');

            // Skip if already imported — but backfill fastest_lap_time_ms if it was null
            $existing = Result::where('race_session_id', $session->id)
                ->where('entry_car_id', $entryCar->id)
                ->first();

            if ($existing) {
                if ($existing->fastest_lap_time_ms === null && $fastestLapMs !== null) {
                    $existing->update([
                        'fastest_lap_time_ms' => $fastestLapMs,
                        'fastest_lap'         => $isFastestLap,
                    ]);
                    $lapRecords->checkAndUpdate($existing->fresh());
                }
                $skipped++;
                continue;
            }

            // Ensure race_entry_cars pivot record exists so the season view can find the car
            \DB::table('race_entry_cars')->insertOrIgnore([
                'calendar_race_id' => $calRace->id,
                'entry_car_id'     => $entryCar->id,
            ]);

            // ── Status & position ─────────────────────────────────────────────
            $status   = $this->mapStatus($primary['positionText']);
            $position = is_numeric($primary['positionText']) ? (int) $primary['position'] : null;

            // ── Gap to leader ─────────────────────────────────────────────────
            $gapMs       = null;
            $gapLapsDown = null;

            if ($status === 'finished' && isset($winnerMs[$raceId])) {
                $driverMs = $this->nullish($primary['milliseconds']);
                if ($driverMs) {
                    $gapMs = (int) $driverMs - $winnerMs[$raceId]; // 0 for the winner
                }
            }

            if (preg_match('/\+(\d+)\s+Lap/', $primary['time'] ?? '', $m)) {
                $gapLapsDown = (int) $m[1];
            }

            // ── Create Result ─────────────────────────────────────────────────
            $result = Result::create([
                'race_session_id'     => $session->id,
                'entry_car_id'        => $entryCar->id,
                'position'            => $position,
                'class_position'      => $position,  // single class — same as overall
                'status'              => $status,
                'laps_completed'      => $this->nullish($primary['laps']) ? (int) $primary['laps'] : 0,
                'gap_to_leader_ms'    => $gapMs,
                'gap_laps_down'       => $gapLapsDown,
                'fastest_lap_time_ms' => $fastestLapMs,
                'fastest_lap'         => $isFastestLap,
                'points_awarded'      => (float) ($primary['points'] ?? 0),
            ]);

            // ── Check lap record ──────────────────────────────────────────────
            $lapRecords->checkAndUpdate($result);

            // ── Create ResultDriver(s) ────────────────────────────────────────
            $order       = 1;
            $seenDrivers = [];

            foreach ($rows as $row) {
                $ergastDriverId = (int) $row['driverId'];
                if (in_array($ergastDriverId, $seenDrivers)) continue;
                $seenDrivers[] = $ergastDriverId;

                $dbDriverId = $driverMap[$ergastDriverId] ?? null;
                if (!$dbDriverId) {
                    $this->warn("  Driver not found (ergast id: {$ergastDriverId})");
                    continue;
                }

                ResultDriver::create([
                    'result_id'    => $result->id,
                    'driver_id'    => $dbDriverId,
                    'driver_order' => $order++,
                ]);
            }

            $created++;
        }

        $this->info("  Created: {$created} | Skipped/missing: {$skipped}");
    }

    /**
     * Import qualifying results from Ergast qualifying.csv.
     * Creates one QualifyingSession ("Qualifying") per race and one
     * QualifyingResult per driver, using the best time across Q1/Q2/Q3.
     *
     * @param array<int, CalendarRace> $raceMap
     * @param array<int, Constructor>  $constructorMap
     * @param array                    $entryCarMap
     */
    private function processQualifying(
        string $path,
        array  $raceMap,
        array  $constructorMap,
        array  $entryCarMap
    ): void {
        $raceIdSet = array_flip(array_keys($raceMap));
        $grouped   = [];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (isset($raceIdSet[(int) $data['raceId']])) {
                $grouped[(int) $data['raceId']][] = $data;
            }
        }

        fclose($handle);

        $created = 0;
        $skipped = 0;

        foreach ($grouped as $raceId => $rows) {
            $calRace = $raceMap[$raceId] ?? null;
            if (!$calRace) { $skipped += count($rows); continue; }

            $session = QualifyingSession::firstOrCreate(
                ['calendar_race_id' => $calRace->id, 'name' => 'Qualifying'],
                ['session_order' => 1]
            );

            foreach ($rows as $row) {
                $constructor = $constructorMap[(int) $row['constructorId']] ?? null;
                if (!$constructor) { $skipped++; continue; }

                $entryCar = $entryCarMap[$constructor->id][$row['number']] ?? null;
                if (!$entryCar) { $skipped++; continue; }

                // Skip if already imported
                if (QualifyingResult::where('qualifying_session_id', $session->id)
                    ->where('entry_car_id', $entryCar->id)->exists()) {
                    $skipped++;
                    continue;
                }

                // Best time across Q1 / Q2 / Q3
                $times = array_filter([
                    $this->parseLapTime($row['q1'] ?? '\N'),
                    $this->parseLapTime($row['q2'] ?? '\N'),
                    $this->parseLapTime($row['q3'] ?? '\N'),
                ], fn($t) => $t !== null && $t > 0);

                QualifyingResult::create([
                    'qualifying_session_id' => $session->id,
                    'entry_car_id'          => $entryCar->id,
                    'position'              => is_numeric($row['position']) ? (int) $row['position'] : null,
                    'best_lap_time_ms'      => !empty($times) ? min($times) : null,
                    'laps_set'              => count($times),
                ]);

                $created++;
            }
        }

        $this->info("  Created: {$created} | Skipped/missing: {$skipped}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Map builders
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<int, CalendarRace> ergast raceId → CalendarRace */
    private function buildRaceMap(int $year, int $seasonId, string $path): array
    {
        $map    = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if ((int) $data['year'] !== $year) continue;

            $calRace = CalendarRace::where('season_id', $seasonId)
                ->where('round_number', (int) $data['round'])
                ->first();

            if ($calRace) $map[(int) $data['raceId']] = $calRace;
        }

        fclose($handle);
        return $map;
    }

    /** @return array<int, int> ergast driverId → DB Driver.id */
    private function buildDriverMap(string $path): array
    {
        $map    = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data   = array_combine($header, $row);
            $driver = Driver::where('first_name', trim($data['forename']))
                ->where('last_name', trim($data['surname']))
                ->first();

            if ($driver) $map[(int) $data['driverId']] = $driver->id;
        }

        fclose($handle);
        return $map;
    }

    /** @return array<int, Constructor> ergast constructorId → Constructor */
    private function buildConstructorMap(string $path, int $worldId): array
    {
        $map    = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data        = array_combine($header, $row);
            $constructor = Constructor::where('world_id', $worldId)
                ->where('name', trim($data['name']))
                ->first();

            if ($constructor) $map[(int) $data['constructorId']] = $constructor;
        }

        fclose($handle);
        return $map;
    }

    /**
     * Build [db_constructor_id][car_number] → EntryCar for the season.
     * Keyed by DB constructor id so it aligns with constructorMap lookups.
     */
    private function buildEntryCarMap(int $seasonId): array
    {
        $map      = [];
        $entryCars = EntryCar::whereHas('entryClass.seasonEntry', function ($q) use ($seasonId) {
            $q->where('season_id', $seasonId);
        })->with('entryClass.seasonEntry')->get();

        foreach ($entryCars as $car) {
            $constructorId             = $car->entryClass->seasonEntry->constructor_id;
            $map[$constructorId][$car->car_number] = $car;
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Map Ergast positionText to the Result status enum.
     * All non-finish retirements collapse to 'dnf' per project convention.
     */
    private function mapStatus(string $positionText): string
    {
        if (is_numeric($positionText))           return 'finished';
        if (in_array($positionText, ['D', 'E'])) return 'dsq';
        if (in_array($positionText, ['W', 'F'])) return 'dns';
        return 'dnf';
    }

    /**
     * Convert an Ergast lap time string to milliseconds.
     * Handles: "1:27.456" (M:SS.mmm), "87.456" (seconds), "87" (whole seconds).
     * Returns null for Ergast null sentinel "\N" or empty/invalid values.
     */
    private function parseLapTime(string $value): ?int
    {
        $clean = trim($value);

        if (!$this->nullish($clean)) return null;

        // M:SS.mmm or M:SS
        if (str_contains($clean, ':')) {
            [$mins, $secs] = explode(':', $clean, 2);
            $ms = ((int) $mins * 60 + (float) $secs) * 1000;
            return $ms > 0 ? (int) $ms : null;
        }

        // Plain seconds (e.g. "87.456")
        $ms = (float) $clean * 1000;
        return $ms > 0 ? (int) $ms : null;
    }

    /**
     * Return null for Ergast's "\N" null sentinel, otherwise the original value.
     */
    private function nullish(string $value): ?string
    {
        return ($value === '\N' || $value === '') ? null : $value;
    }
}
