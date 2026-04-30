<?php

namespace App\Services;

use App\Models\Result;
use App\Models\ResultDriver;
use App\Models\CalendarRace;
use App\Models\EntryCar;
use App\Models\RaceSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Points\PointsCalculationService;

class ResultService
{
    protected PointsCalculationService $pointsService;

    public function __construct(PointsCalculationService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    protected array $allowedStatuses = ['finished', 'dnf', 'dsq', 'dns'];

    public function saveRaceResults(array $data): void
    {
        if (empty($data['race_session_id'])) {
            throw ValidationException::withMessages([
                'race_session_id' => 'Race session is required.'
            ]);
        }

        $raceSession = \App\Models\RaceSession::with([
            'calendarRace.season.pointSystem.bonusRules',
            'calendarRace.pointSystem.bonusRules',
            'calendarRace.entryCars.entryClass.raceClass',
            'calendarRace.qualifyingSessions.results'
        ])->findOrFail($data['race_session_id']);

        $race = $raceSession->calendarRace;

        if ($race->isLocked()) {
            throw ValidationException::withMessages([
                'race' => 'This race is locked and cannot be modified.'
            ]);
        }
        
        $this->validateRaceResults($race, $data);
        $this->calculateClassPositions($race, $data['results']);
        // Calculate points
        
        $this->pointsService->calculateWeekendPoints($race, $data['results'],0);
        
        

        DB::transaction(function () use ($data, $raceSession) {

            // Delete old results for this session only
            Result::where('race_session_id', $raceSession->id)->delete();

            foreach ($data['results'] as $resultData) {

                if (empty($resultData['entry_car_id'])) {
                    continue;
                }

                unset($resultData['drivers']);

                $resultData['race_session_id'] = $raceSession->id;
                
                $result = Result::create($resultData);

                // Freeze drivers directly from entry car
                $this->freezeDriversFromEntryCar(
                    $result,
                    $resultData['entry_car_id']
                );
            }
        });
    }

    public function saveSprintRaceResults(array $data): void
    {
        if (empty($data['race_session_id'])) {
            throw ValidationException::withMessages([
                'race_session_id' => 'Race session is required.'
            ]);
        }

        $raceSession = \App\Models\RaceSession::with([
            'calendarRace.season.pointSystem.bonusRules',
            'calendarRace.pointSystem.bonusRules',
            'calendarRace.entryCars.entryClass.raceClass',
            'calendarRace.qualifyingSessions.results'
        ])->findOrFail($data['race_session_id']);

        $race = $raceSession->calendarRace;

        if ($race->isLocked()) {
            throw ValidationException::withMessages([
                'race' => 'This race is locked and cannot be modified.'
            ]);
        }

        $this->validateRaceResults($race, $data);
        $this->calculateClassPositions($race, $data['results']);
        // Calculate points
        $this->pointsService->calculateWeekendPoints($race, $data['results'],1);

        DB::transaction(function () use ($data, $raceSession) {

            // Delete old results for this session only
            Result::where('race_session_id', $raceSession->id)->delete();

            foreach ($data['results'] as $resultData) {

                if (empty($resultData['entry_car_id'])) {
                    continue;
                }

                unset($resultData['drivers']);

                $resultData['race_session_id'] = $raceSession->id;

                $result = Result::create($resultData);

                // Freeze drivers directly from entry car
                $this->freezeDriversFromEntryCar(
                    $result,
                    $resultData['entry_car_id']
                );
            }
        });
    }
    /*
    |--------------------------------------------------------------------------
    | Validation Layer
    |--------------------------------------------------------------------------
    */

    protected function validateRaceResults($race, array &$data): void
    {
        if (empty($data['results'])) {
            throw ValidationException::withMessages([
                'results' => 'No race results provided.'
            ]);
        }

        $positions = [];
        $fastestLapCandidate = null;

        /*
        |--------------------------------------------------------------------------
        | First Pass — Validate & Detect Fastest Lap
        |--------------------------------------------------------------------------
        */
        
        foreach ($data['results'] as $index => &$result) {
            
            // Convert fastest lap string to milliseconds
            if (!empty($result['fastest_lap'])) {
                $result['fastest_lap_time_ms'] =
                    $this->convertLapToMs($result['fastest_lap']);
            } else {
                $result['fastest_lap_time_ms'] = null;
            }
            // Convert gap input
            if (!empty($result['gap'])) {

                $gap = trim($result['gap']);

                // If format is +XL
                if (preg_match('/^\+(\d+)L$/i', $gap, $matches)) {

                    $result['gap_laps_down'] = (int) $matches[1];
                    $result['gap_to_leader_ms'] = null;
                } else {

                    // Convert time format m:ss:ms
                    $ms = $this->convertLapToMs($gap);

                    $result['gap_to_leader_ms'] = $ms;
                    $result['gap_laps_down'] = null;
                }
            } else {

                $result['gap_to_leader_ms'] = null;
                $result['gap_laps_down'] = null;
            }

            // Skip empty rows
            if (empty($result['entry_car_id'])) {
                continue;
            }

            $entryCar = $race->entryCars
                ->firstWhere('id', $result['entry_car_id']);

            if (!$entryCar) {
                throw ValidationException::withMessages([
                    "results.$index.entry_car_id" =>
                    'Entry car does not belong to this race.'
                ]);
            }

            // Status validation
            if (!in_array($result['status'], $this->allowedStatuses)) {
                throw ValidationException::withMessages([
                    "results.$index.status" =>
                    'Invalid race status.'
                ]);
            }

            // Position validation (overall unique)
            if (is_null($result['position'])) {
                throw ValidationException::withMessages([
                    "results.$index.position" =>
                    'Overall position is required.'
                ]);
            }

            if (in_array($result['position'], $positions)) {
                throw ValidationException::withMessages([
                    "results.$index.position" =>
                    'Duplicate overall finishing position detected.'
                ]);
            }

            $positions[] = $result['position'];

            // Laps validation
            if (($result['laps_completed'] ?? 0) < 0) {
                throw ValidationException::withMessages([
                    "results.$index.laps_completed" =>
                    'Laps completed cannot be negative.'
                ]);
            }

            // Leader gap rule
            if ($result['position'] == 1) {
                $result['gap_to_leader_ms'] = null;
                $result['gap_laps_down'] = null;
            }

            // Detect fastest lap (any driver with a recorded time is eligible)
            if (!is_null($result['fastest_lap_time_ms'])) {
                if (
                    !$fastestLapCandidate ||
                    $result['fastest_lap_time_ms'] <
                    $fastestLapCandidate['time']
                ) {
                    $fastestLapCandidate = [
                        'index' => $index,
                        'time' => $result['fastest_lap_time_ms']
                    ];
                }
            }

            // Reset fastest flag (we decide)
            $result['fastest_lap'] = false;
        }

        /*
        |--------------------------------------------------------------------------
        | Apply Fastest Lap
        |--------------------------------------------------------------------------
        */

        if ($fastestLapCandidate) {
            $data['results'][$fastestLapCandidate['index']]['fastest_lap'] = true;
        }

    }

    protected function freezeDriversFromEntryCar($result, int $entryCarId): void
    {
        $entryCar = \App\Models\EntryCar::with('drivers')
            ->find($entryCarId);

        if (!$entryCar) return;

        foreach ($entryCar->drivers as $index => $driver) {

            \App\Models\ResultDriver::create([
                'result_id'    => $result->id,
                'driver_id'    => $driver->id,
                'driver_order' => $index + 1,
            ]);
        }
    }

    protected function convertLapToMs(?string $lap): ?int
    {
        if (!$lap) return null;

        $parts = explode(':', $lap);

        if (count($parts) !== 3) return null;

        [$minutes, $seconds, $milliseconds] = $parts;

        if (!is_numeric($minutes) || !is_numeric($seconds) || !is_numeric($milliseconds)) {
            return null;
        }

        return ((int)$minutes * 60000)
            + ((int)$seconds * 1000)
            + (int)$milliseconds;
    }

    protected function calculateClassPositions($race, array &$results): void
    {
        // Build a lookup: race_class_id → SeasonClass (for name + sub_class)
        $classMap = [];
        foreach ($race->entryCars as $car) {
            $seasonClass = $car->entryClass->raceClass ?? null;
            if ($seasonClass) {
                $classMap[$car->entryClass->race_class_id] = $seasonClass;
            }
        }

        // class_position  — position among ALL cars sharing the same class name
        // sub_class_position — position within the specific sub-class (null when no sub_class)
        $classNameCounters = [];
        $subClassCounters  = [];

        $sorted = collect($results)
            ->filter(fn($r) => !is_null($r['position']))
            ->sortBy('position')
            ->values();

        foreach ($sorted as $sortedResult) {
            $entryCar = $race->entryCars->firstWhere('id', $sortedResult['entry_car_id']);
            if (!$entryCar) continue;

            $classId     = $entryCar->entryClass->race_class_id;
            $seasonClass = $classMap[$classId] ?? null;
            $className   = $seasonClass?->name ?? "class_{$classId}";
            $hasSubClass = !is_null($seasonClass?->sub_class);

            if (!isset($classNameCounters[$className])) {
                $classNameCounters[$className] = 1;
            }
            if ($hasSubClass && !isset($subClassCounters[$classId])) {
                $subClassCounters[$classId] = 1;
            }

            foreach ($results as &$result) {
                if ($result['entry_car_id'] == $sortedResult['entry_car_id']) {
                    $result['class_position']     = $classNameCounters[$className];
                    $result['sub_class_position'] = $hasSubClass
                        ? $subClassCounters[$classId]
                        : null;
                }
            }

            $classNameCounters[$className]++;
            if ($hasSubClass) {
                $subClassCounters[$classId]++;
            }
        }
    }
}
