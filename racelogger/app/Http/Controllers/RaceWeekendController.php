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

        // Create race sessions if none exist
        if ($race->raceSessions()->count() === 0) {

            // Sprint only applies to single-race weekends
            if ($hasSprint == 1 && $numberOfRaces == 1) {
                $race->raceSessions()->create([
                    'name'         => 'Sprint',
                    'session_order' => 0,
                    'is_sprint'    => true,
                    'reverse_grid' => false,
                ]);
            }

            for ($i = 1; $i <= $numberOfRaces; $i++) {
                $race->raceSessions()->create([
                    'name'         => $numberOfRaces > 1 ? "Race $i" : 'Race',
                    'session_order' => $i,
                    'is_sprint'    => false,
                    'reverse_grid' => false,
                ]);
            }
        }

        // Reload relationship after creation
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

        return Inertia::render('races/weekend/manage', [
            'race'                 => $race,
            'defaultTab'           => $defaultTab,
            'activeRaceSession'    => $activeRaceSession,
            'raceSessionsByNumber' => $raceSessionsByNumber,
            'sprintRaceSession'    => $sprintRaceSession,
            'hasSprint'            => $hasSprint,
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
                    'tab'    => 'results',
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
