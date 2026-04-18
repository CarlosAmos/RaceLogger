<?php

namespace App\Services;

use App\Models\Result;
use App\Models\CalendarRace;
use App\Models\PointSystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChampionshipScenarioService
{
    public function getScenario($seasonId, $entryClassId)
    {


        $standings = $this->getStandings($seasonId, $entryClassId);

        //dump($standings);

        if ($standings->count() < 2) {
            return null;
        }

        $leader = $standings[0];
        $rivals = $standings->slice(1);

        $remainingRaces = CalendarRace::where('season_id', $seasonId)
            ->where('is_locked', 0)
            ->orderBy('race_date')
            ->get();

        if ($remainingRaces->count() < 1) {
            return null;
        }

        $nextRace = $remainingRaces->first();

        $maxAfterNext = $this->maxPointsRemainingAfterNextRace($remainingRaces);

        $nextRacePoints = $this->getPointsForRace($nextRace);

        foreach ($nextRacePoints as $position => $points) {

            $leaderTotal = $leader->points + $points;

            $clinched = true;

            foreach ($rivals as $rival) {

                $rivalMax = $rival->points + $maxAfterNext;

                if ($leaderTotal <= $rivalMax) {
                    $clinched = false;
                    break;
                }
            }

            if ($clinched) {
                return [
                    'leader' => $leader,
                    'position_needed' => $position,
                    'race' => $nextRace
                ];
            }
        }

        return null;
    }

    private function getStandings($seasonId, $seasonClassId)
    {
        return Result::selectRaw('entry_car_id, SUM(points_awarded) as points')
            ->whereHas('entryCar.entryClass', function ($q) use ($seasonClassId) {
                $q->where('race_class_id', $seasonClassId);
            })
            ->whereHas('raceSession.calendarRace', function ($q) use ($seasonId) {
                $q->where('season_id', $seasonId)
                    ->where('is_locked', 1);
            })
            ->with('entryCar')
            ->groupBy('entry_car_id')
            ->orderByDesc('points')
            ->get();
    }

    private function maxPointsRemainingAfterNextRace($remainingRaces)
    {
        $racesAfterNext = $remainingRaces->slice(1);

        $max = 0;

        foreach ($racesAfterNext as $race) {
            $points = $this->getPointsForRace($race);
            if (!empty($points)) {
                $max += max($points);
            }
        }

        return $max;
    }

    private function getPointsForRace($race)
    {
        $pointSystemId = $race->point_system_id
            ?? $race->season->point_system_id;

        // Get race finishing points
        $racePoints = \DB::table('point_system_rules')
            ->where('point_system_id', $pointSystemId)
            ->where('type', 'race')
            ->orderBy('position')
            ->pluck('points', 'position')
            ->toArray();

        // Get bonus points (fastest lap etc.)
        $bonusPoints = \DB::table('point_system_bonus_rules')
            ->where('point_system_id', $pointSystemId)
            ->sum('points');

        // Add bonus to each finishing position
        foreach ($racePoints as $position => $points) {
            $racePoints[$position] = $points + $bonusPoints;
        }

        return $racePoints;
    }

public function getClinchTable($seasonId, $seasonClassId)
{
    $standings = $this->getStandings($seasonId, $seasonClassId);

    if ($standings->count() < 2) {
        return null;
    }

    $leader = $standings[0];
    $rivals = $standings->slice(1);

    $remainingRaces = CalendarRace::where('season_id', $seasonId)
        ->where('is_locked', 0)
        ->orderBy('race_date')
        ->get();

    if ($remainingRaces->count() === 0) {
        return null;
    }

    $nextRace = $remainingRaces->first();

    $pointsTable = $this->getPointsForRace($nextRace);

    $maxAfterNext = $this->maxPointsRemainingAfterNextRace($remainingRaces);

    if (empty($pointsTable)) {
        return null;
    }

    $maxRacePoints = max($pointsTable);

    /*
    -------------------------------------------------------
    Filter rivals still mathematically in contention
    -------------------------------------------------------
    */

    $rivals = $rivals->filter(function ($rival) use ($leader, $maxRacePoints, $maxAfterNext) {

        $rivalMaxPossible = $rival->points + $maxRacePoints + $maxAfterNext;

        return $rivalMaxPossible >= $leader->points;
    });

    if ($rivals->count() === 0) {
        return null;
    }

    /*
    -------------------------------------------------------
    Check if championship can be won next race
    -------------------------------------------------------
    */

    $titleCanBeWonNextRace = false;

    foreach ($pointsTable as $leaderPos => $leaderPoints) {

        $leaderTotal = $leader->points + $leaderPoints;

        $allRivalsBeaten = true;

        foreach ($rivals as $rival) {

            $rivalMaxPossible = $rival->points + $maxAfterNext;

            if ($rivalMaxPossible >= $leaderTotal) {
                $allRivalsBeaten = false;
                break;
            }
        }

        if ($allRivalsBeaten) {
            $titleCanBeWonNextRace = true;
            break;
        }
    }

    if (!$titleCanBeWonNextRace) {
        return null;
    }

    /*
    -------------------------------------------------------
    Build scenario table
    -------------------------------------------------------
    */

    $rows = [];

    foreach ($pointsTable as $leaderPos => $leaderPoints) {

        $leaderTotal = $leader->points + $leaderPoints;

        $row = [
            'leader_pos' => $leaderPos,
            'rivals' => []
        ];

        foreach ($rivals as $rival) {

            $required = null;

            // copy the table so we don't modify the original
            $rivalPointsTable = $pointsTable;

            // add position outside the points
            $maxPos = max(array_keys($rivalPointsTable));
            $rivalPointsTable[$maxPos + 1] = 0;

            // now reverse for the search
            $reversePoints = array_reverse($rivalPointsTable, true);

            foreach ($reversePoints as $rivalPos => $rivalPoints) {

                $rivalTotal = $rival->points + $rivalPoints + $maxAfterNext;

                if ($rivalTotal >= $leaderTotal) {
                    $required = $rivalPos;
                    break;
                }
            }

            $row['rivals'][$rival->entry_car_id] = $required;
        }
        
        $rows[] = $row;
    }

    return [
        'leader' => $leader,
        'rivals' => $rivals,
        'rows' => $rows
    ];
}
}
