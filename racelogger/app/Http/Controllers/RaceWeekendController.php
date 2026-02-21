<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRace;
use App\Services\ResultService;
use App\Services\QualifyingService;
use Illuminate\Support\Facades\DB;

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
            'season.seasonEntries.entryClasses.entryCars.drivers',
            'results.drivers',
            'qualifyingSessions.results'
        ]);

        $participantsExist = $race->entryCars()->exists();

        // If tab is explicitly requested, use it
        $defaultTab = request('tab');

        if (!$defaultTab) {
            if (!$participantsExist) {
                $defaultTab = 'participants';
            } elseif (!$race->results()->exists()) {
                $defaultTab = 'qualifying';
            } else {
                $defaultTab = 'race';
            }
        }

        return view('races.weekend.manage', [
            'race' => $race,
            'defaultTab' => $defaultTab
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

$submittedTab = $request->input('submitted_tab');

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
    }

    if ($submittedTab === 'race') {

        $resultService->saveRaceResults([
            'calendar_race_id' => $race->id,
            'results' => $request->input('results')
        ]);
    }

    DB::commit();

} catch (\Throwable $e) {

    DB::rollBack();
    dd($e->getMessage());
}

        DB::transaction(function () use (
            $request,
            $race,
            $qualifyingService,
            $resultService
        ) {});

        // 🔥 Redirect to qualifying tab if participants exist
        if ($race->entryCars()->exists()) {
            return redirect()
                ->route('races.show', ['race' => $race->id, 'tab' => 'qualifying'])
                ->with('success', 'Participants saved.');
        }

        return redirect()
            ->route('races.show', ['race' => $race->id, 'tab' => 'participants'])
            ->with('success', 'Participants updated.');
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
