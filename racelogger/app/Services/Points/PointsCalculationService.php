<?php

namespace App\Services\Points;

use App\Models\CalendarRace;
use App\Models\PointsSystem;
use Illuminate\Validation\ValidationException;

class PointsCalculationService
{
    public function calculateWeekendPoints($race, array &$results, $sprintRace): void
    {
        $pointSystem = \App\Models\PointSystem::with(['rules', 'bonusRules'])->find(13);

        if (!$pointSystem) {
            foreach ($results as &$result) {
                $result['points_awarded'] = 0;
            }
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

            $classPosition = $result['class_position'] ?? null;

            if ($classPosition) {

                $rule = $raceRules
                    ->firstWhere('position', $classPosition);

                if ($rule) {
                    $points += $rule->points;
                }
            }

            $result['points_awarded'] = $points;
        }
 
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
                                $result['entry_car_id'] ==
                                $qualiResult->entry_car_id
                            ) {
                                $result['points_awarded'] +=
                                    $rule->points;
                            }
                        }
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
        }
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
