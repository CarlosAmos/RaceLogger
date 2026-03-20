<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Season;
use App\Models\SeasonClass;
use App\Models\Constructor;
use App\Models\Entrant;
use App\Models\SeasonEntry;
use App\Models\EntryClass;
use App\Models\CarModel;
use App\Models\EntryCar;
use App\Models\Driver;
use App\Models\CalendarRace;
use App\Models\RaceSession;

class ImportF1Entries extends Command
{
    protected $signature = 'import:f1-entries {year} {--series=8} {--world=1}';
    protected $description = 'Import F1 season entries (season entries, entrants, cars, drivers, race sessions) from Ergast dataset';

    public function handle(): int
    {
        $year     = (int) $this->argument('year');
        $seriesId = (int) $this->option('series');
        $worldId  = 1;
        $base     = storage_path('app/f1_import');

        $required = ['drivers.csv', 'constructors.csv', 'races.csv', 'results.csv'];
        foreach ($required as $file) {
            if (!file_exists("{$base}/{$file}")) {
                $this->error("Missing: {$base}/{$file}");
                return 1;
            }
        }

        // ── 1. Season ────────────────────────────────────────────────────────
        $season = Season::where('year', $year)->where('series_id', $seriesId)->first();
        if (!$season) {
            $this->error("Season {$year} (series_id={$seriesId}) not found. Run import:f1-seasons first.");
            return 1;
        }

        // ── 2. Race ID map: ergast raceId → CalendarRace ─────────────────────
        $raceMap = $this->buildRaceMap($year, $season->id, "{$base}/races.csv");
        if (empty($raceMap)) {
            $this->error("No matching CalendarRace records found for {$year}. Run import:f1-calendar {$year} first.");
            return 1;
        }
        $ergastRaceIds = array_keys($raceMap);

        // ── 3. Which races have sprint data ───────────────────────────────────
        $sprintPath    = "{$base}/sprint_results.csv";
        $sprintRaceIds = file_exists($sprintPath)
            ? $this->getSprintRaceIds($sprintPath, $ergastRaceIds)
            : [];

        // ── 4. Lookup maps ────────────────────────────────────────────────────
        $driverMap      = $this->buildDriverMap("{$base}/drivers.csv");
        $constructorMap = $this->buildConstructorMap("{$base}/constructors.csv", $worldId);

        $this->info("Driver map:      " . count($driverMap)      . " entries");
        $this->info("Constructor map: " . count($constructorMap) . " entries");

        // ── 5. Season class ───────────────────────────────────────────────────
        $seasonClass = SeasonClass::firstOrCreate(
            ['season_id' => $season->id, 'name' => 'Overall'],
            ['display_order' => 1]
        );

        // ── 6. Load results and group by constructor → car number ─────────────
        //
        // Structure:  $entries[ergastConstructorId][carNumber] = [driverIds]
        //
        $entries = [];

        $handle = fopen("{$base}/results.csv", 'r');
        $header = fgetcsv($handle);
        $raceIdSet = array_flip($ergastRaceIds);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $raceId = (int) $data['raceId'];

            if (!isset($raceIdSet[$raceId])) continue;

            $cid = (int) $data['constructorId'];
            $num = $data['number'];           // car number as string (can be "\N" for historical)
            $did = (int) $data['driverId'];

            if (!isset($entries[$cid][$num])) {
                $entries[$cid][$num] = [];
            }
            if (!in_array($did, $entries[$cid][$num])) {
                $entries[$cid][$num][] = $did;
            }
        }

        fclose($handle);
        $this->info("Found " . count($entries) . " constructors in {$year} results\n");

        // ── 7. Create entrants, entries, cars, drivers ────────────────────────
        foreach ($entries as $ergastConstructorId => $cars) {

            if (!isset($constructorMap[$ergastConstructorId])) {
                $this->warn("  Constructor not in DB (ergast id: {$ergastConstructorId}) — skipping");
                continue;
            }

            $constructor = $constructorMap[$ergastConstructorId];

            // Entrant (one per constructor — can be renamed manually later)
            $entrant = Entrant::firstOrCreate(
                ['world_id' => $worldId, 'name' => $constructor->name]
            );

            // SeasonEntry — series_id in match so it's always set correctly
            $seasonEntry = SeasonEntry::firstOrCreate(
                [
                    'season_id'  => $season->id,
                    'series_id'  => $seriesId,
                    'entrant_id' => $entrant->id,
                ],
                ['constructor_id' => $constructor->id]
            );

            // EntryClass (single class — Overall)
            $entryClass = EntryClass::firstOrCreate(
                ['season_entry_id' => $seasonEntry->id, 'race_class_id' => $seasonClass->id]
            );

            // Placeholder CarModel — one per constructor per year
            $carModel = CarModel::firstOrCreate(
                ['constructor_id' => $constructor->id, 'name' => "{$constructor->name} {$year}"],
                ['year' => $year]
            );

            $this->line("  [{$constructor->name}]");

            foreach ($cars as $carNumber => $driverIds) {

                $entryCar = EntryCar::firstOrCreate(
                    ['entry_class_id' => $entryClass->id, 'car_number' => (string) $carNumber],
                    ['car_model_id' => $carModel->id]
                );

                $assigned = 0;
                $missed   = 0;

                foreach ($driverIds as $ergastDriverId) {
                    if (!isset($driverMap[$ergastDriverId])) {
                        $this->warn("    Driver not in DB (ergast id: {$ergastDriverId})");
                        $missed++;
                        continue;
                    }

                    $dbDriverId = $driverMap[$ergastDriverId];

                    // Attach only if not already assigned to this car
                    $already = \DB::table('entry_car_driver')
                        ->where('entry_car_id', $entryCar->id)
                        ->where('driver_id', $dbDriverId)
                        ->exists();

                    if (!$already) {
                        \DB::table('entry_car_driver')->insert([
                            'entry_car_id' => $entryCar->id,
                            'driver_id'    => $dbDriverId,
                        ]);
                        $assigned++;
                    }
                }

                $driverCount = count($driverIds);
                $suffix      = $driverCount > 1 ? " ⚠ {$driverCount} drivers (mid-season swap)" : "";
                $this->info("    Car #{$carNumber} — {$assigned} driver(s) assigned{$suffix}");

                if ($missed > 0) {
                    $this->warn("    {$missed} driver(s) not found in DB for car #{$carNumber}");
                }
            }
        }

        // ── 8. Race sessions ──────────────────────────────────────────────────
        $this->info("\nCreating race sessions...");
        $sprintSet = array_flip($sprintRaceIds);

        foreach ($raceMap as $ergastRaceId => $calRace) {

            $hasSprint = isset($sprintSet[$ergastRaceId]);

            // Sprint session comes before main race in session_order
            if ($hasSprint) {
                RaceSession::firstOrCreate(
                    ['calendar_race_id' => $calRace->id, 'name' => 'Sprint Race'],
                    ['session_order' => 1, 'is_sprint' => true, 'reverse_grid' => false]
                );

                // Flag the CalendarRace as having a sprint
                $calRace->update(['sprint_race' => true]);
            }

            RaceSession::firstOrCreate(
                ['calendar_race_id' => $calRace->id, 'name' => 'Race'],
                ['session_order' => $hasSprint ? 2 : 1, 'is_sprint' => false, 'reverse_grid' => false]
            );

            $label = $hasSprint ? 'Race + Sprint' : 'Race';
            $this->line("  Round {$calRace->round_number}: {$label}");
        }

        $this->info("\nEntries import complete for {$year}.");
        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build ergast raceId → CalendarRace map for the given year/season.
     *
     * @return array<int, CalendarRace>
     */
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

            if ($calRace) {
                $map[(int) $data['raceId']] = $calRace;
            } else {
                $this->warn("  No CalendarRace for round {$data['round']} ({$data['name']})");
            }
        }

        fclose($handle);
        return $map;
    }

    /**
     * Return the set of ergast race IDs that appear in sprint_results.csv.
     *
     * @param  int[] $raceIds
     * @return int[]
     */
    private function getSprintRaceIds(string $path, array $raceIds): array
    {
        $set    = array_flip($raceIds);
        $found  = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data    = array_combine($header, $row);
            $raceId  = (int) $data['raceId'];
            if (isset($set[$raceId]) && !in_array($raceId, $found)) {
                $found[] = $raceId;
            }
        }

        fclose($handle);
        return $found;
    }

    /**
     * Build ergast driverId → DB Driver.id map using forename/surname matching.
     *
     * @return array<int, int>
     */
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

            if ($driver) {
                $map[(int) $data['driverId']] = $driver->id;
            }
        }

        fclose($handle);
        return $map;
    }

    /**
     * Build ergast constructorId → Constructor model map using name matching.
     *
     * @return array<int, Constructor>
     */
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

            if ($constructor) {
                $map[(int) $data['constructorId']] = $constructor;
            }
        }

        fclose($handle);
        return $map;
    }
}
