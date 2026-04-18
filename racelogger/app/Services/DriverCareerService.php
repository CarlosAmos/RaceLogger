<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DriverCareerService
{
    public function getCareerStructure($driverId, int $worldId): Collection
    {
        $entries = $this->getDriverSeasonEntries($driverId, $worldId);
        $standings = $this->calculateChampionshipStandings();

        return $entries->groupBy('season_year')
            ->sortKeys()
            ->map(function ($yearGroup) use ($driverId, $standings) {
                return $yearGroup->groupBy('season_id')->map(function ($seasonGroup) use ($driverId, $standings) {
                    $first = $seasonGroup->first();

                    $stats = $this->aggregateStatsForSeason($driverId, $first->season_id);

                    $position = $standings->where('season_id', $first->season_id)
                        ->where('driver_id', $driverId)
                        ->first()->rank ?? null;

                    return [
                        'season_id'   => $first->season_id,
                        'series_name' => $first->series_name,
                        'teams'       => $seasonGroup->unique('entrant_name')->pluck('entrant_name'),
                        'stats'       => $stats,
                        'ordinal'     => $position ? $this->getOrdinalSuffix($position) : '-',
                        'position'    => $position,
                    ];
                });
            });
    }

    private function getDriverSeasonEntries($driverId, int $worldId): Collection
    {
        // Assigned drivers
        $assigned = DB::table('entry_car_driver as ecd')
            ->join('entry_cars as ec', 'ecd.entry_car_id', '=', 'ec.id')
            ->join('entry_classes as ecl', 'ec.entry_class_id', '=', 'ecl.id')
            ->join('season_entries as se', 'ecl.season_entry_id', '=', 'se.id')
            ->join('seasons as s', 'se.season_id', '=', 's.id')
            ->leftJoin('series as ser', 's.series_id', '=', 'ser.id')
            ->leftJoin('entrants as e', 'se.entrant_id', '=', 'e.id')
            ->where('ecd.driver_id', $driverId)
            ->where('ser.world_id', $worldId)
            ->select(['s.id as season_id', 's.year as season_year', 'ser.name as series_name', 'e.name as entrant_name']);

        // Results drivers (for one-offs/mid-season)
        $results = DB::table('result_drivers as rd')
            ->join('results as r', 'rd.result_id', '=', 'r.id')
            ->join('entry_cars as ec', 'r.entry_car_id', '=', 'ec.id')
            ->join('entry_classes as ecl', 'ec.entry_class_id', '=', 'ecl.id')
            ->join('season_entries as se', 'ecl.season_entry_id', '=', 'se.id')
            ->join('seasons as s', 'se.season_id', '=', 's.id')
            ->leftJoin('series as ser', 's.series_id', '=', 'ser.id')
            ->leftJoin('entrants as e', 'se.entrant_id', '=', 'e.id')
            ->where('rd.driver_id', $driverId)
            ->where('ser.world_id', $worldId)
            ->select(['s.id as season_id', 's.year as season_year', 'ser.name as series_name', 'e.name as entrant_name']);

        return $assigned->union($results)->get();
    }

    public function aggregateStatsForSeason(int $driverId, int $seasonId): object
    {
        $sql = <<<SQL
            SELECT 
                SUM(season_car_stats.races) as races,
                SUM(season_car_stats.wins) as wins,
                SUM(season_car_stats.podiums) as podiums,
                SUM(season_car_stats.fastest_laps) as fastest_laps,
                SUM(season_car_stats.total_points) as total_points,
                SUM(season_car_stats.poles) as poles,
                /* If any race in the season is NOT locked, we consider the season in progress */
                MAX(season_car_stats.has_unlocked_races) as is_active_season
            FROM (
                SELECT 
                    COUNT(DISTINCT r.id) as races,
                    /* Fixes WEC: Grab class position so P1 in class doesn't show as P2 overall */
                    COUNT(DISTINCT CASE WHEN r.class_position = 1 THEN r.id END) as wins,
                    COUNT(DISTINCT CASE WHEN r.class_position <= 3 AND r.class_position > 0 THEN r.id END) as podiums,
                    SUM(r.points_awarded) as total_points,
                    
                    /* Check the locked status from the calendar_races table */
                    CASE WHEN MIN(cr.is_locked) = 0 THEN 1 ELSE 0 END as has_unlocked_races,

                    /* Fastest Lap — use the flag set by ResultService */
                    COUNT(DISTINCT CASE WHEN r.fastest_lap = 1 THEN r.id END) as fastest_laps,

                    /* Poles (Car-Aware) */
                    (SELECT COUNT(DISTINCT qr.id) 
                    FROM qualifying_results qr 
                    JOIN qualifying_sessions qs ON qr.qualifying_session_id = qs.id
                    JOIN calendar_races cr2 ON qs.calendar_race_id = cr2.id
                    WHERE cr2.season_id = cr.season_id 
                    AND qr.position = 1 
                    AND qr.entry_car_id = r.entry_car_id) as poles

                FROM result_drivers rd
                INNER JOIN results r ON rd.result_id = r.id
                INNER JOIN race_sessions rs ON r.race_session_id = rs.id
                INNER JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                
                WHERE rd.driver_id = ? 
                AND cr.season_id = ? 
                AND rs.name LIKE '%Race%'
                GROUP BY cr.season_id, r.entry_car_id
            ) as season_car_stats;
    SQL;

        $results = DB::selectOne($sql, [$driverId, $seasonId]);

        // Fallback for empty seasons
        if (!$results || !$results->races) {
            return (object)[
                'races' => 0,
                'wins' => 0,
                'podiums' => 0,
                'poles' => 0,
                'fastest_laps' => 0,
                'points' => 0,
                'season_active' => 0
            ];
        }
        $seasonInProgess = $this->isSeasonInProgress($seasonId);
        return (object)[
            'races'        => (int)$results->races,
            'wins'         => (int)$results->wins,
            'podiums'      => (int)$results->podiums,
            'poles'        => (int)$results->poles,
            'fastest_laps' => (int)$results->fastest_laps,
            'points'       => (fmod($results->total_points, 1) == 0)
                ? (int)$results->total_points
                : (float)$results->total_points,
            'season_active' => $seasonInProgess,
        ];
    }

    private function calculateChampionshipStandings(): Collection
    {
        return collect(DB::select("
            SELECT season_id, driver_id, race_class_id, rank FROM (
                SELECT
                    cr.season_id,
                    rd.driver_id,
                    ecl.race_class_id,
                    DENSE_RANK() OVER (
                        PARTITION BY cr.season_id, ecl.race_class_id
                        ORDER BY SUM(r.points_awarded) DESC, SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
                    ) as rank
                FROM result_drivers rd
                JOIN results r         ON rd.result_id = r.id
                JOIN race_sessions rs  ON r.race_session_id = rs.id
                JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                JOIN entry_cars ec     ON r.entry_car_id = ec.id
                JOIN entry_classes ecl ON ec.entry_class_id = ecl.id
                WHERE rs.name LIKE '%Race%'
                GROUP BY cr.season_id, rd.driver_id, ecl.race_class_id
            ) as standings
        "));
    }

    public function isSeasonInProgress(int $seasonId): bool
    {
        // If there is ANY race in this season that is NOT locked, the season is in progress
        return DB::table('calendar_races')
            ->where('season_id', $seasonId)
            ->where('is_locked', 0)
            ->exists(); 
    }

    private function getOrdinalSuffix($number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }
}
