<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRace;
use Inertia\Inertia;
use App\Services\ResultService;
use App\Services\QualifyingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaceWeekendController extends Controller
{
    public function show(CalendarRace $race, Request $request)
    {
        if ($race->isLocked()) {
            return $this->showDetail($race);
        }

        return $this->showManage($race, $request);
    }

    public function showManage(CalendarRace $race, Request $request)
    {
        $race->load([
            'season.seasonEntries.entryClasses.raceClass',
            'season.seasonEntries.entryClasses.entryCars.entryClass.raceClass',
            'season.seasonEntries.entryClasses.entryCars.entryClass.seasonEntry.entrant',
            'season.seasonEntries.entryClasses.entryCars.carModel',
            'season.seasonEntries.entryClasses.entryCars.drivers',
            'entryCars.entryClass.raceClass',
            'entryCars.entryClass.seasonEntry.entrant',
            'entryCars.carModel',
            'entryCars.drivers',
            'raceSessions.results',
            'qualifyingSessions.results',
        ]);

        $hasSprint      = request('has_sprint');
        $numberOfRaces  = $race->number_of_races ?? 1;

        $participantsExist = $race->entryCars()->exists();

        $hasQualifyingResults = $race->qualifyingSessions()
            ->whereHas('results')
            ->exists();

        $hasRaceResults = $race->raceSessions()
            ->whereHas('results')
            ->exists();

        $defaultTab = request('tab');

        $existingNonSprint = $race->raceSessions->where('is_sprint', false);

        // Create sprint session if needed (single-race only)
        if ($hasSprint == 1 && $numberOfRaces == 1 && $race->raceSessions->where('is_sprint', true)->isEmpty()) {
            $race->raceSessions()->create([
                'name'          => 'Sprint',
                'session_order' => 0,
                'is_sprint'     => true,
                'reverse_grid'  => false,
            ]);
        }

        // Create any missing non-sprint race sessions up to number_of_races
        $existingOrders = $existingNonSprint->pluck('session_order')->all();
        for ($i = 1; $i <= $numberOfRaces; $i++) {
            if (!in_array($i, $existingOrders)) {
                $race->raceSessions()->create([
                    'name'          => $numberOfRaces > 1 ? "Race $i" : 'Race',
                    'session_order' => $i,
                    'is_sprint'     => false,
                    'reverse_grid'  => false,
                ]);
            }
        }

        // Reload relationship
        $race->load('raceSessions.results');

        /** @var \Illuminate\Support\Collection $raceSessionsByNumber Keyed by session_order (= race number) */
        $raceSessionsByNumber = $race->raceSessions
            ->where('is_sprint', false)
            ->keyBy('session_order');

        $sprintRaceSession  = $race->raceSessions->firstWhere('is_sprint', true);
        $activeRaceSession  = $raceSessionsByNumber->get(1); // backward-compat for single race

        if (!$defaultTab) {
            if (!$participantsExist) {
                $defaultTab = 'participants';
            } elseif (!$hasQualifyingResults) {
                $defaultTab = $numberOfRaces > 1 ? 'q_1' : 'qualifying';
            } else {
                $defaultTab = $numberOfRaces > 1 ? 'r_1' : 'race';
            }
        }

        $accSessionData = $this->parseAccSessionData($race->id, $numberOfRaces);

        return Inertia::render('races/weekend/manage', [
            'race'                 => $race,
            'defaultTab'           => $defaultTab,
            'activeRaceSession'    => $activeRaceSession,
            'raceSessionsByNumber' => $raceSessionsByNumber,
            'sprintRaceSession'    => $sprintRaceSession,
            'hasSprint'            => $hasSprint,
            'accSessionData'       => $accSessionData,
        ]);
    }

    protected function showDetail(CalendarRace $race)
    {
        $race->load([
            'results.drivers.driver',
            'qualifyingSessions.results'
        ]);

        return view('races.weekend.detail', compact('race'));
    }

    public function update(
        Request $request,
        CalendarRace $race,
        QualifyingService $qualifyingService,
        ResultService $resultService
    ) {
        $action       = $request->input('action', 'save');
        $submittedTab = $request->input('submitted_tab');
        $hasSprint    = request('has_sprint');
        $numberOfRaces = $race->number_of_races ?? 1;
        $nextTab      = $numberOfRaces > 1 ? 'q_1' : 'qualifying';

        try {
            DB::beginTransaction();

            /*
            |------------------------------------------------------------------
            | Participants
            |------------------------------------------------------------------
            */
            if ($submittedTab === 'participants') {
                $participants = $request->input('participants', []);
                $race->entryCars()->sync($participants);
                $nextTab = $numberOfRaces > 1 ? 'q_1' : 'qualifying';
            }

            /*
            |------------------------------------------------------------------
            | Qualifying  (key: 'qualifying' for single race, 'q_N' for multi)
            |------------------------------------------------------------------
            */
            if ($submittedTab === 'qualifying' || str_starts_with($submittedTab, 'q_')) {
                $raceNumber = str_starts_with($submittedTab, 'q_')
                    ? (int) substr($submittedTab, 2)
                    : 1;

                $data = $request->input('qualifying');

                // Delete only sessions belonging to this race number
                $race->qualifyingSessions()->where('race_number', $raceNumber)->delete();

                foreach ($data['sessions'] as $sessionIndex => $sessionData) {
                    $sessionName = ($data['format'] == 1)
                        ? 'Qualifying'
                        : 'Q' . ($sessionIndex + 1);

                    $session = $race->qualifyingSessions()->create([
                        'name'          => $sessionName,
                        'session_order' => $sessionIndex + 1,
                        'race_number'   => $raceNumber,
                    ]);

                    foreach ($sessionData['results'] ?? [] as $resultData) {
                        if (empty($resultData['entry_car_id'])) {
                            continue;
                        }

                        $session->results()->create([
                            'entry_car_id'     => $resultData['entry_car_id'],
                            'position'         => $resultData['position'],
                            'best_lap_time_ms' => $this->convertLapToMs($resultData['best_lap'] ?? null),
                        ]);
                    }
                }

                if ($numberOfRaces > 1) {
                    $nextTab = "r_{$raceNumber}";
                } else {
                    $nextTab = $hasSprint == 1 ? 'sprint_race' : 'race';
                }
            }

            /*
            |------------------------------------------------------------------
            | Sprint Race (single-race weekends only)
            |------------------------------------------------------------------
            */
            if ($submittedTab === 'sprint_race') {
                $resultService->saveSprintRaceResults([
                    'race_session_id' => $request->input('sprint_race_session_id'),
                    'results'         => $request->input('spr_results', []),
                ]);
                $nextTab = 'race';
            }

            /*
            |------------------------------------------------------------------
            | Race Results  (key: 'race' for single, 'r_N' for multi)
            |------------------------------------------------------------------
            */
            error_log("MADE IT TO HERE");
            if ($submittedTab === 'race' || str_starts_with($submittedTab, 'r_')) {
                $raceNumber   = str_starts_with($submittedTab, 'r_')
                    ? (int) substr($submittedTab, 2)
                    : 1;
                $raceSessionId = $request->input('race_session_id');

                if (!$raceSessionId) {
                    throw ValidationException::withMessages([
                        'race_session' => 'Race session not selected.',
                    ]);
                }

                $resultService->saveRaceResults([
                    'race_session_id' => $raceSessionId,
                    'results'         => $request->input('results', []),
                ]);

                // Advance to next race if there is one
                $nextTab = ($numberOfRaces > 1 && $raceNumber < $numberOfRaces)
                    ? "q_" . ($raceNumber + 1)
                    : ($numberOfRaces > 1 ? "r_{$numberOfRaces}" : 'race');
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }

        if ($action === 'complete') {
            $race->update(['is_locked' => true]);

            return redirect()
                ->route('seasons.show', [
                    'season' => $race->season->id,
                    'tab'    => 'calender',
                ])
                ->with('success', 'Weekend completed successfully.');
        }

        return redirect()
            ->route('races.show', [
                'race'      => $race->id,
                'tab'       => $nextTab,
                'has_sprint' => $hasSprint,
            ])
            ->with('success', ucfirst(str_replace('_', ' ', $submittedTab)) . ' saved successfully.');
    }

    /**
     * Read and parse ACC result JSON files for a race.
     *
     * Returns:
     *   [
     *     'qualifying' => [ 1 => [...lines], 2 => [...lines], ... ],  // keyed by file number
     *     'race'       => [ 1 => [...lines], 2 => [...lines], ... ],  // keyed by race number
     *   ]
     *
     * Qualifying file N maps to the N-th qualifying session across the whole weekend,
     * following ACC's sequential numbering (Qualifying_1.json, Qualifying_2.json, …).
     */
    protected function parseAccSessionData(int $raceId, int $numberOfRaces): array
    {
        $accSessionData = ['qualifying' => [], 'race' => []];
        $accDir = public_path("acc_races/{$raceId}");

        if (!is_dir($accDir)) {
            return $accSessionData;
        }

        // Read every Qualifying_N.json found (up to 20 to avoid infinite scan)
        for ($n = 1; $n <= 20; $n++) {
            $qualFile = "{$accDir}/Qualifying_{$n}.json";
            if (!file_exists($qualFile)) {
                break;
            }
            $raw   = $this->decodeUtf16(file_get_contents($qualFile));
            $data  = json_decode($raw, true);
            $lines = $data['snapShot']['leaderBoardLines'] ?? [];
            $accSessionData['qualifying'][$n] = array_map(fn($line) => [
                'race_number'  => $line['car']['raceNumber'],
                'best_lap_ms'  => ($line['timing']['bestLap'] ?? 0) > 0 ? $line['timing']['bestLap'] : null,
                'cup_category' => $line['car']['cupCategory'] ?? null,
            ], $lines);
        }

        // Read Race_N.json for each race number
        for ($n = 1; $n <= $numberOfRaces; $n++) {
            $raceFile = "{$accDir}/Race_{$n}.json";
            if (!file_exists($raceFile)) {
                continue;
            }
            $raw         = $this->decodeUtf16(file_get_contents($raceFile));
            $data        = json_decode($raw, true);
            $lines       = $data['snapShot']['leaderBoardLines'] ?? [];
            $leaderLaps  = $lines[0]['timing']['lapCount']  ?? 0;
            $leaderTotal = $lines[0]['timing']['totalTime'] ?? 0;
            $accSessionData['race'][$n] = array_map(fn($line) => [
                'race_number'    => $line['car']['raceNumber'],
                'lap_count'      => $line['timing']['lapCount'] ?? 0,
                'fastest_lap_ms' => ($line['timing']['bestLap'] ?? 0) > 0 ? $line['timing']['bestLap'] : null,
                'gap_ms'         => ($line['timing']['lapCount'] ?? 0) >= $leaderLaps && ($line['timing']['totalTime'] ?? 0) > $leaderTotal
                    ? ($line['timing']['totalTime'] - $leaderTotal)
                    : null,
                'gap_laps'       => $leaderLaps - ($line['timing']['lapCount'] ?? 0) > 0
                    ? $leaderLaps - $line['timing']['lapCount']
                    : null,
            ], $lines);
        }

        return $accSessionData;
    }

    /**
     * Decode a UTF-16 LE or BE byte string (with or without BOM) to UTF-8.
     */
    protected function decodeUtf16(string $raw): string
    {
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            return mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        }
        if (substr($raw, 0, 2) === "\xFE\xFF") {
            return mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
        }
        return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
    }

    /**
     * Convert a lap time string (M:SS:mmm) to milliseconds.
     */
    protected function convertLapToMs(?string $lap): ?int
    {
        if (!$lap) return null;

        $parts = explode(':', $lap);

        if (count($parts) !== 3) return null;

        [$minutes, $seconds, $milliseconds] = $parts;

        return ((int)$minutes * 60 * 1000)
            + ((int)$seconds * 1000)
            + (int)$milliseconds;
    }
}
