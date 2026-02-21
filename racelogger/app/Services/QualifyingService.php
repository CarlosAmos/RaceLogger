<?php

namespace App\Services;

use App\Models\CalendarRace;
use App\Models\QualifyingSession;
use App\Models\QualifyingResult;
use App\Models\EntryCar;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QualifyingService
{
    public function saveQualifying(array $data): void
    {
        $race = CalendarRace::with('season')->findOrFail($data['calendar_race_id']);

        if ($race->isLocked()) {
            throw ValidationException::withMessages([
                'race' => 'This race is locked and cannot be modified.'
            ]);
        }

        $this->validateQualifying($race, $data);

        DB::transaction(function () use ($race, $data) {

            // Clean replace strategy
            QualifyingSession::where('calendar_race_id', $race->id)->delete();

            foreach ($data['sessions'] as $sessionData) {

                $results = $sessionData['results'];
                unset($sessionData['results']);

                $sessionData['calendar_race_id'] = $race->id;

                $session = QualifyingSession::create($sessionData);

                foreach ($results as $resultData) {

                    $resultData['qualifying_session_id'] = $session->id;

                    QualifyingResult::create($resultData);
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Layer
    |--------------------------------------------------------------------------
    */

    protected function validateQualifying($race, array $data): void
    {
        if (empty($data['sessions'])) {
            throw ValidationException::withMessages([
                'sessions' => 'At least one qualifying session is required.'
            ]);
        }

        $sessionOrders = [];

        foreach ($data['sessions'] as $sIndex => $session) {

            // 1️⃣ Session order must be unique
            if (in_array($session['session_order'], $sessionOrders)) {
                throw ValidationException::withMessages([
                    "sessions.$sIndex.session_order" =>
                        'Duplicate session order detected.'
                ]);
            }

            $sessionOrders[] = $session['session_order'];

            if (empty($session['results'])) {
                throw ValidationException::withMessages([
                    "sessions.$sIndex.results" =>
                        'Qualifying session must contain results.'
                ]);
            }

            $positions = [];
            $entryCars = [];

            foreach ($session['results'] as $rIndex => $result) {

                $entryCar = EntryCar::where('id', $result['entry_car_id'])
                    ->where('id', $result['entry_car_id'])
                    ->first();

                // 2️⃣ Entry car must belong to season
                if (!$entryCar) {
                    throw ValidationException::withMessages([
                        "sessions.$sIndex.results.$rIndex.entry_car_id" =>
                            'Entry car does not belong to this race season.'
                    ]);
                }

                // 3️⃣ Unique entry car per session
                if (in_array($result['entry_car_id'], $entryCars)) {
                    throw ValidationException::withMessages([
                        "sessions.$sIndex.results.$rIndex.entry_car_id" =>
                            'Duplicate entry car in qualifying session.'
                    ]);
                }

                $entryCars[] = $result['entry_car_id'];

                // 4️⃣ Unique positions per session
                if (!is_null($result['position'])) {

                    if (in_array($result['position'], $positions)) {
                        throw ValidationException::withMessages([
                            "sessions.$sIndex.results.$rIndex.position" =>
                                'Duplicate qualifying position detected.'
                        ]);
                    }

                    $positions[] = $result['position'];
                }

                // 5️⃣ Lap time validation
                if (
                    is_null($result['best_lap_time_ms']) &&
                    is_null($result['average_lap_time_ms'])
                ) {
                    throw ValidationException::withMessages([
                        "sessions.$sIndex.results.$rIndex.best_lap_time_ms" =>
                            'At least one lap time must be provided.'
                    ]);
                }

                // 6️⃣ Negative time check
                if (
                    (!is_null($result['best_lap_time_ms']) &&
                        $result['best_lap_time_ms'] < 0) ||
                    (!is_null($result['average_lap_time_ms']) &&
                        $result['average_lap_time_ms'] < 0)
                ) {
                    throw ValidationException::withMessages([
                        "sessions.$sIndex.results.$rIndex.best_lap_time_ms" =>
                            'Lap times cannot be negative.'
                    ]);
                }

                // 7️⃣ Laps set validation
                if (
                    isset($result['laps_set']) &&
                    $result['laps_set'] < 0
                ) {
                    throw ValidationException::withMessages([
                        "sessions.$sIndex.results.$rIndex.laps_set" =>
                            'Laps set cannot be negative.'
                    ]);
                }
            }
        }
    }
}