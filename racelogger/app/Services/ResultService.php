<?php

namespace App\Services;

use App\Models\Result;
use App\Models\ResultDriver;
use App\Models\CalendarRace;
use App\Models\EntryCar;
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
        $race = CalendarRace::with([
            'season.pointSystem',
            'pointSystem',
            'qualifyingSessions.results'
        ])->findOrFail($data['calendar_race_id']);

        if ($race->isLocked()) {
            throw ValidationException::withMessages([
                'race' => 'This race is locked and cannot be modified.'
            ]);
        }
        $this->validateRaceResults($race, $data);

        $this->pointsService->calculateWeekendPoints($race, $data['results']);

        DB::transaction(function () use ($data, $race) {

            Result::where('calendar_race_id', $race->id)->delete();

            foreach ($data['results'] as $resultData) {

                $drivers = $resultData['drivers'];
                unset($resultData['drivers']);

                $resultData['calendar_race_id'] = $race->id;

                $result = Result::create($resultData);

                $this->freezeDrivers($result->id, $drivers);
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

        $entryCars = [];
        $positions = [];
        $fastestLapCandidate = null;

        foreach ($data['results'] as $index => &$result) {

            $entryCar = EntryCar::with('drivers')
                ->where('id', $result['entry_car_id'])
                ->first();

            // 1️⃣ Entry car must belong to season
            if (!$entryCar) {
                throw ValidationException::withMessages([
                    "results.$index.entry_car_id" =>
                    'Entry car does not belong to this race season.'
                ]);
            }

            // 2️⃣ Unique entry cars
            if (in_array($result['entry_car_id'], $entryCars)) {
                throw ValidationException::withMessages([
                    "results.$index.entry_car_id" =>
                    'Duplicate entry car detected.'
                ]);
            }
            $entryCars[] = $result['entry_car_id'];

            // 3️⃣ Status validation
            if (!in_array($result['status'], $this->allowedStatuses)) {
                throw ValidationException::withMessages([
                    "results.$index.status" =>
                    'Invalid race status.'
                ]);
            }

            // 4️⃣ Position validation
            if (!is_null($result['position'])) {

                if (in_array($result['position'], $positions)) {
                    throw ValidationException::withMessages([
                        "results.$index.position" =>
                        'Duplicate finishing position detected.'
                    ]);
                }

                $positions[] = $result['position'];

                if ($result['status'] === 'finished' && !$result['position']) {
                    throw ValidationException::withMessages([
                        "results.$index.position" =>
                        'Finished cars must have a position.'
                    ]);
                }
            }

            // 5️⃣ Leader gap rule
            if ($result['position'] === 1) {
                $result['gap_to_leader_ms'] = null;
            }

            // 6️⃣ Laps validation
            if (($result['laps_completed'] ?? 0) < 0) {
                throw ValidationException::withMessages([
                    "results.$index.laps_completed" =>
                    'Laps completed cannot be negative.'
                ]);
            }

            // 7️⃣ Drivers must belong to entry car
            $entryDriverIds = $entryCar->drivers->pluck('id')->toArray();

            if (empty($result['drivers'])) {
                throw ValidationException::withMessages([
                    "results.$index.drivers" =>
                    'At least one driver must be assigned.'
                ]);
            }

            foreach ($result['drivers'] as $driverId) {
                if (!in_array($driverId, $entryDriverIds)) {
                    throw ValidationException::withMessages([
                        "results.$index.drivers" =>
                        'Driver does not belong to this entry car.'
                    ]);
                }
            }

            if (count($result['drivers']) !== count(array_unique($result['drivers']))) {
                throw ValidationException::withMessages([
                    "results.$index.drivers" =>
                    'Duplicate drivers detected.'
                ]);
            }

            // 8️⃣ Auto detect fastest lap
            if (
                !is_null($result['fastest_lap_time_ms']) &&
                $result['status'] === 'finished'
            ) {
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

            // Always reset fastest_lap (we determine it)
            $result['fastest_lap'] = false;
        }

        // Apply fastest lap automatically
        if ($fastestLapCandidate) {
            $data['results'][$fastestLapCandidate['index']]['fastest_lap'] = true;
        }
    }

    protected function freezeDrivers(int $resultId, array $driverIds): void
    {
        foreach ($driverIds as $index => $driverId) {
            ResultDriver::create([
                'result_id' => $resultId,
                'driver_id' => $driverId,
                'driver_order' => $index + 1,
            ]);
        }
    }
}
