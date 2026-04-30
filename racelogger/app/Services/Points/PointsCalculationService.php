<?php

namespace App\Services\Points;

use App\Models\CalendarRace;
use App\Models\PointsSystem;
use Illuminate\Validation\ValidationException;

class PointsCalculationService
{
    public function calculateWeekendPoints($race, array &$results, $sprintRace): void
    {
        if($sprintRace == 0) {
            $pointSystem = $race->pointSystem
                ?? $race->season->pointSystem;
        } else {
            $pointSystem = \App\Models\PointSystem::with(['rules', 'bonusRules'])->find(13);
        }





        if (!$pointSystem) {
            foreach ($results as &$result) {
                $result['points_awarded'] = 0;
            }
            unset($result);
            return;
        }

        $pointSystem->load(['rules', 'bonusRules']);

        $rules = $pointSystem->rules;
        $bonusRules = $pointSystem->bonusRules;

        $raceRules = $rules->where('type', 'race');
        $qualifyingRules = $rules->where('type', 'qualifying');

        $fastestLapRule = $bonusRules
            ->firstWhere('type', 'fastest_lap');

       
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Base Race Points
        |--------------------------------------------------------------------------
        */

        foreach ($results as &$result) {

            if (empty($result['entry_car_id'])) {
                continue;
            }

            $points = 0;

            // Only award race points to drivers who finished
            if (($result['status'] ?? '') === 'finished') {
                $classPosition = $result['class_position'] ?? null;

                if ($classPosition) {
                    $rule = $raceRules->firstWhere('position', $classPosition);

                    if ($rule) {
                        $points += $rule->points;
                    }
                }
            }

            $result['points_awarded'] = $points;
        }
        unset($result);


        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Qualifying Points (Final Session Only)
        |--------------------------------------------------------------------------
        */

        if ($qualifyingRules->isNotEmpty()) {

            $finalSession = $race->qualifyingSessions
                ->sortByDesc('session_order')
                ->first();

            if ($finalSession && $finalSession->results->isNotEmpty()) {

                $classQualifyingPositions = [];

                foreach (
                    $finalSession->results->sortBy('position')
                    as $qualiResult
                ) {

                    $entryCar = $race->entryCars
                        ->firstWhere('id', $qualiResult->entry_car_id);

                    if (!$entryCar) continue;

                    $classId = $entryCar->entryClass->race_class_id;

                    if (!isset($classQualifyingPositions[$classId])) {
                        $classQualifyingPositions[$classId] = 1;
                    }

                    $classPosition =
                        $classQualifyingPositions[$classId];

                    $rule = $qualifyingRules
                        ->firstWhere('position', $classPosition);

                    if ($rule) {

                        foreach ($results as &$result) {

                            if (
                                $result['entry_car_id'] == $qualiResult->entry_car_id &&
                                ($result['status'] ?? '') === 'finished'
                            ) {
                                $result['points_awarded'] += $rule->points;
                            }
                        }
                        unset($result);
                    }

                    $classQualifyingPositions[$classId]++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Fastest Lap Bonus
        |--------------------------------------------------------------------------
        */


        if ($fastestLapRule) {

            foreach ($results as &$result) {

                if (
                    empty($result['fastest_lap']) ||
                    empty($result['entry_car_id'])
                ) {
                    continue;
                }

                $eligible = true;

                if (
                    $fastestLapRule->requires_finish &&
                    $result['status'] !== 'finished'
                ) {
                    $eligible = false;
                }

                if (
                    !is_null($fastestLapRule->min_position_required) &&
                    (
                        is_null($result['class_position']) ||
                        $result['class_position'] >
                        $fastestLapRule->min_position_required
                    )
                ) {
                    $eligible = false;
                }

                if ($eligible) {
                    $result['points_awarded'] +=
                        $fastestLapRule->points;
                }
            }
            unset($result);
        }



        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Sub-class Points (delta from class points, swapping race position component)
        |--------------------------------------------------------------------------
        | For entries with a sub_class_position, points_awarded used class_position for
        | race pts. We swap just that component; qualifying and FL bonuses are inherited.
        */

        // Track race-position-only points per entry car
        $classPosPoints    = []; // entry_car_id → pts from class_position
        $subClassPosPoints = []; // entry_car_id → pts from sub_class_position

        $ko = 0;
        foreach ($results as $result) {

            if (empty($result['entry_car_id']) || ($result['status'] ?? '') !== 'finished') {
                continue;
            }

            $id = $result['entry_car_id'];
             $ko++;



            if (!empty($result['class_position'])) {
                // if ($result['entry_car_id'] == 3749) {
                //     error_log("*******************************1 HERE Id: {$id}");
                //     error_log(json_encode($results));
                //     error_log("*******************************2 HERE Id: {$id}");
                // }
                $rule = $raceRules->firstWhere('position', $result['class_position']);
                $classPosPoints[$id] = $rule ? (float) $rule->points : 0;


            }

            if (!is_null($result['sub_class_position'] ?? null)) {
                $rule = $raceRules->firstWhere('position', $result['sub_class_position']);
                $subClassPosPoints[$id] = $rule ? (float) $rule->points : 0;

                if ($result['entry_car_id'] == 3776) {
                    
                }
            }
        }

        foreach ($results as &$result) {
            if (empty($result['entry_car_id'])) {
                continue;
            }

            if (is_null($result['sub_class_position'] ?? null)) {
                $result['sub_class_points_awarded'] = null;
                continue;
            }

            $id          = $result['entry_car_id'];
            $classPts    = $classPosPoints[$id]    ?? 0;
            $subClassPts = $subClassPosPoints[$id] ?? 0;

            $result['sub_class_points_awarded'] = (float) $result['points_awarded'] - $classPts + $subClassPts;
        }
        unset($result);
    }

    protected function calculateQualifyingPoints($race, $qualifyingRules)
    {
        if ($qualifyingRules->isEmpty()) {
            return [];
        }

        $pointsByEntryCar = [];

        $sessions = $race->qualifyingSessions()->with('results')->get();

        foreach ($sessions as $session) {

            foreach ($session->results as $qualResult) {

                if (!$qualResult->position) {
                    continue;
                }

                $rule = $qualifyingRules->get($qualResult->position);

                if (!$rule) {
                    continue;
                }

                $entryCarId = $qualResult->entry_car_id;

                if (!isset($pointsByEntryCar[$entryCarId])) {
                    $pointsByEntryCar[$entryCarId] = 0;
                }

                $pointsByEntryCar[$entryCarId] += $rule->points;
            }
        }

        return $pointsByEntryCar;
    }

    protected function resolvePointSystem(CalendarRace $race)
    {
        $pointSystem = $race->pointSystem ?? $race->season->pointSystem;

        if (!$pointSystem) {
            throw ValidationException::withMessages([
                'points' => 'No point system assigned.'
            ]);
        }

        return $pointSystem;
    }
}
