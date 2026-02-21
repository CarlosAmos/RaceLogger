<?php

namespace App\Services\Points;

use App\Models\CalendarRace;
use Illuminate\Validation\ValidationException;

class PointsCalculationService
{
    public function calculateWeekendPoints(CalendarRace $race, array &$results): void
    {
        $pointSystem = $this->resolvePointSystem($race);

        $raceRules = $pointSystem->rules()
            ->where('type', 'race')
            ->get()
            ->keyBy('position');

        $qualifyingRules = $pointSystem->rules()
            ->where('type', 'qualifying')
            ->get()
            ->keyBy('position');

        $fastestLapRule = $pointSystem->bonusRules()
            ->where('type', 'fastest_lap')
            ->first();

        // Get qualifying results grouped by entry_car
        $qualifyingPointsByEntryCar = $this->calculateQualifyingPoints($race, $qualifyingRules);

        foreach ($results as &$result) {

            $points = 0;

            // 🏁 Race Position Points
            if (
                $result['status'] === 'finished' &&
                !is_null($result['position'])
            ) {
                $rule = $raceRules->get($result['position']);
                if ($rule) {
                    $points += $rule->points;
                }
            }

            // ⚡ Fastest Lap Bonus
            if (!empty($result['fastest_lap']) && $fastestLapRule) {

                $eligible = true;

                // Must finish?
                if ($fastestLapRule->requires_finish && $result['status'] !== 'finished') {
                    $eligible = false;
                }

                // Minimum position required?
                if (
                    $fastestLapRule->min_position_required &&
                    (
                        is_null($result['position']) ||
                        $result['position'] > $fastestLapRule->min_position_required
                    )
                ) {
                    $eligible = false;
                }

                if ($eligible) {
                    $points += $fastestLapRule->points;
                }
            }

            // 🟣 Qualifying Points
            $entryCarId = $result['entry_car_id'];
            if (isset($qualifyingPointsByEntryCar[$entryCarId])) {
                $points += $qualifyingPointsByEntryCar[$entryCarId];
            }

            // ✏ Manual override (final authority)
            if (isset($result['points_override'])) {
                $points = $result['points_override'];
            }

            $result['points_awarded'] = $points;
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