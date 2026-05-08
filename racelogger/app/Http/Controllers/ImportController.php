<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\TrackLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class ImportController extends Controller
{
    /**
     * Show the F1 import page with a CSV preview for the given year.
     */
    public function index(Request $request)
    {
        $year    = $request->integer('year', 0);
        $preview = $year > 0 ? $this->buildPreview($year) : null;

        return Inertia::render('import/index', compact('year', 'preview'));
    }

    /**
     * Run one of the F1 import commands for the given year and step.
     *
     * Steps: seasons | calendar | entries | results
     */
    public function run(Request $request)
    {
        $request->validate([
            'year'               => 'required|integer|min:1950|max:2099',
            'step'               => 'required|in:seasons,calendar,entries,results',
            'layout_assignments' => 'nullable|array',
        ]);

        $year = (int) $request->input('year');
        $step = $request->input('step');

        $commandMap = [
            'seasons'  => 'import:f1-seasons',
            'calendar' => 'import:f1-calendar',
            'entries'  => 'import:f1-entries',
            'results'  => 'import:f1-results',
        ];

        $command = $commandMap[$step];
        $params  = match ($step) {
            'seasons'  => [],
            'calendar' => [
                'year'               => $year,
                '--layout-overrides' => json_encode($request->input('layout_assignments', [])),
            ],
            default    => ['year' => $year],
        };

        $output = '';
        try {
            $exitCode = Artisan::call($command, $params);
            $output   = Artisan::output();
            $success  = $exitCode === 0;
        } catch (\Exception $e) {
            $output  = $e->getMessage();
            $success = false;
        }

        return back()->with([
            'import_success' => $success,
            'import_output'  => $output,
            'import_step'    => $step,
        ]);
    }

    /**
     * Parse CSV files and return a preview array for the given year.
     *
     * @return array{races: array, constructors: array, drivers: array, missing_files: string[]}
     */
    private function buildPreview(int $year): array
    {
        $base          = storage_path('app/f1_import');
        $requiredFiles = ['races.csv', 'constructors.csv', 'drivers.csv', 'results.csv'];
        $missingFiles  = [];

        foreach ($requiredFiles as $file) {
            if (!file_exists("{$base}/{$file}")) {
                $missingFiles[] = $file;
            }
        }

        if (!empty($missingFiles)) {
            return ['races' => [], 'constructors' => [], 'drivers' => [], 'missing_files' => $missingFiles];
        }

        // ── Circuit names from circuits.csv ──────────────────────────────────
        $circuitNames = [];
        if (file_exists("{$base}/circuits.csv")) {
            $handle = fopen("{$base}/circuits.csv", 'r');
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                $circuitNames[(int) $data['circuitId']] = $data['name'];
            }
            fclose($handle);
        }

        // Pre-load all track names (lowercase) for matching
        $dbTrackNames = Track::pluck('name')->map(fn($n) => mb_strtolower($n))->all();

        // ── Races for the given year ──────────────────────────────────────────
        $races       = [];
        $ergastRaceIds = [];
        $handle      = fopen("{$base}/races.csv", 'r');
        $header      = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if ((int) $data['year'] !== $year) continue;
            $raceId      = (int) $data['raceId'];
            $circuitId   = (int) $data['circuitId'];
            $circuitName = $circuitNames[$circuitId] ?? null;

            // Match if any DB track name appears in the circuit name or vice versa
            $trackFound = false;
            if ($circuitName) {
                $lower = mb_strtolower($circuitName);
                foreach ($dbTrackNames as $dbName) {
                    if (str_contains($lower, $dbName) || str_contains($dbName, $lower)) {
                        $trackFound = true;
                        break;
                    }
                }
            }

            $ergastRaceIds[] = $raceId;
            $races[]         = [
                'id'           => $raceId,
                'round'        => (int) $data['round'],
                'name'         => $data['name'],
                'date'         => $data['date'] !== '\N' ? $data['date'] : null,
                'circuit_name' => $circuitName,
                'track_found'  => $trackFound,
            ];
        }
        fclose($handle);

        usort($races, fn($a, $b) => $a['round'] <=> $b['round']);

        if (empty($ergastRaceIds)) {
            return ['races' => [], 'constructors' => [], 'drivers' => [], 'missing_files' => []];
        }

        $raceIdSet = array_flip($ergastRaceIds);

        // ── Constructors and their drivers for this year ──────────────────────
        $constructorNames = [];
        $handle = fopen("{$base}/constructors.csv", 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $constructorNames[(int) $data['constructorId']] = $data['name'];
        }
        fclose($handle);

        $driverNames = [];
        $handle = fopen("{$base}/drivers.csv", 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            $driverNames[(int) $data['driverId']] = trim($data['forename'] . ' ' . $data['surname']);
        }
        fclose($handle);

        // Build constructor → drivers map from results
        $constructors = [];
        $handle = fopen("{$base}/results.csv", 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            if (!isset($raceIdSet[(int) $data['raceId']])) continue;

            $cid = (int) $data['constructorId'];
            $did = (int) $data['driverId'];

            if (!isset($constructors[$cid])) {
                $constructors[$cid] = [
                    'id'      => $cid,
                    'name'    => $constructorNames[$cid] ?? "Constructor #{$cid}",
                    'drivers' => [],
                ];
            }
            if (!in_array($did, $constructors[$cid]['drivers'], true)) {
                $constructors[$cid]['drivers'][] = $did;
            }
        }
        fclose($handle);

        // Replace driver IDs with names and sort by constructor name
        $constructorList = array_values($constructors);
        usort($constructorList, fn($a, $b) => strcmp($a['name'], $b['name']));

        foreach ($constructorList as &$c) {
            $c['drivers'] = array_map(
                fn($did) => $driverNames[$did] ?? "Driver #{$did}",
                $c['drivers']
            );
        }
        unset($c);

        // ── Track layouts active for this year (for assignment dropdown) ─────
        $layouts = TrackLayout::with('track')
            ->activeForYear($year)
            ->orderBy('track_id')
            ->get()
            ->map(fn($l) => [
                'id'         => $l->id,
                'track_name' => $l->track->name,
                'layout_name' => $l->name,
                'active_from' => $l->active_from,
                'active_to'   => $l->active_to,
            ])
            ->sortBy('track_name')
            ->values()
            ->all();

        return [
            'races'         => $races,
            'constructors'  => $constructorList,
            'drivers'       => [],
            'missing_files' => [],
            'layouts'       => $layouts,
        ];
    }
}
