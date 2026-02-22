<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRace;
use App\Services\ResultService;
use App\Services\QualifyingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaceWeekendController extends Controller
{
    public function show(CalendarRace $race)
    {
        if ($race->isLocked()) {
            return $this->showDetail($race);
        }

        return $this->showManage($race);
    }

    protected function showManage(CalendarRace $race)
    {
        $race->load([
            'entryCars',
            'raceSessions.results.drivers',
            'qualifyingSessions.results'
        ]);

        $participantsExist = $race->entryCars()->exists();

        $hasQualifyingResults = $race->qualifyingSessions()
            ->whereHas('results')
            ->exists();

        $hasRaceResults = $race->raceSessions()
            ->whereHas('results')
            ->exists();

        $defaultTab = request('tab');

        if (!$defaultTab) {

            if (!$participantsExist) {
                $defaultTab = 'participants';
            } elseif (!$hasQualifyingResults) {
                $defaultTab = 'qualifying';
            } elseif (!$hasRaceResults) {
                $defaultTab = 'race';
            } else {
                $defaultTab = 'race';
            }
        }

        if ($race->raceSessions()->count() === 0) {

            $race->raceSessions()->create([
                'name' => 'Race',
                'session_order' => 1,
                'is_sprint' => false,
                'reverse_grid' => false,
            ]);
        }

        // Reload relationship
        $race->load('raceSessions');

        $raceSessionId = request('race_session_id');

        $activeRaceSession = $race->raceSessions->first();

        if ($raceSessionId) {
            $activeRaceSession = $race->raceSessions
                ->firstWhere('id', $raceSessionId)
                ?? $activeRaceSession;
        }

        return view('races.weekend.manage', [
            'race' => $race,
            'defaultTab' => $defaultTab,
            'activeRaceSession' => $activeRaceSession
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
        $action = $request->input('action', 'save');
        $submittedTab = $request->input('submitted_tab');
        $nextTab = "qualifying";
        try {

            DB::beginTransaction();

            if ($submittedTab === 'participants') {

                $participants = $request->input('participants', []);
                $race->entryCars()->sync($participants);
            }

            if ($submittedTab === 'qualifying') {

                $data = $request->input('qualifying');

                $race->qualifyingSessions()->delete();

                foreach ($data['sessions'] as $sessionIndex => $sessionData) {

                    $sessionName = ($data['format'] == 1)
                        ? 'Qualifying'
                        : 'Q' . ($sessionIndex + 1);

                    $session = $race->qualifyingSessions()->create([
                        'name'           => $sessionName,
                        'session_order'  => $sessionIndex + 1,
                        // 'is_elimination' => $data['elimination_enabled'] ?? false,
                        // 'final_target'   => $data['final_target'] ?? null,
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
                $nextTab = 'race';
            }

            /*
                |--------------------------------------------------------------------------
                | Race Results (Per Session)
                |--------------------------------------------------------------------------
                */
            if ($submittedTab === 'race') {

                $raceSessionId = $request->input('race_session_id');

                if (!$raceSessionId) {
                    throw ValidationException::withMessages([
                        'race_session' => 'Race session not selected.'
                    ]);
                }

                $resultService->saveRaceResults([
                    'race_session_id' => $raceSessionId,
                    'results' => $request->input('results', [])
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {

            DB::rollBack();
            dd($e->getMessage());
        }

        // 🔥 Redirect to qualifying tab if participants exist
        // if ($race->entryCars()->exists()) {
        //     return redirect()
        //         ->route('races.show', ['race' => $race->id, 'tab' => 'qualifying'])
        //         ->with('success', 'Participants saved.');
        // }

        if ($action === 'complete') {

            // Optional: mark race as completed/locked
            $race->update([
                'is_completed' => true
            ]);

            return redirect()
                ->route('seasons.show', [
                    'season' => $race->season->id,
                    'tab' => 'results'
                ])
                ->with('success', 'Weekend completed successfully.');
        }

        return redirect()
            ->route('races.show', [
                'race' => $race->id,
                'tab' => $nextTab
            ])
            ->with('success', ucfirst($submittedTab) . ' saved successfully.');
    }

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
