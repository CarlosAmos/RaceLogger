<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DriverCareerService
{
    public function getCareerStructure($driverId, int $worldId): Collection
    {
        $entries                     = $this->getDriverSeasonEntries($driverId, $worldId);
        $overallStandings            = $this->calculateOverallClassStandings();
        $subClassStandings           = $this->calculateChampionshipStandings();
        $enduranceStandings          = $this->calculateChampionshipStandingsFiltered(1);
        $sprintStandings             = $this->calculateChampionshipStandingsFiltered(0);
        $overallEnduranceStandings   = $this->calculateOverallClassStandingsFiltered(1);
        $overallSprintStandings      = $this->calculateOverallClassStandingsFiltered(0);

        return $entries->groupBy('season_year')
            ->sortKeys()
            ->map(function ($yearGroup) use ($driverId, $overallStandings, $subClassStandings, $enduranceStandings, $sprintStandings, $overallEnduranceStandings, $overallSprintStandings) {
                return $yearGroup->groupBy('season_id')->map(function ($seasonGroup) use ($driverId, $overallStandings, $subClassStandings, $enduranceStandings, $sprintStandings, $overallEnduranceStandings, $overallSprintStandings) {
                    $first = $seasonGroup->first();

                    $stats = $this->aggregateStatsForSeason($driverId, $first->season_id);

                    // Overall class position — pools all sub-classes together, ranked by points_awarded
                    $position = $overallStandings
                        ->where('season_id', $first->season_id)
                        ->where('driver_id', $driverId)
                        ->where('class_name', $first->class_name)
                        ->first()->rank ?? null;

                    $subClassRows = $this->getSubClassRows(
                        $driverId,
                        $first->season_id,
                        $first->series_name,
                        $first->class_name,
                        $subClassStandings,
                        $enduranceStandings,
                        $sprintStandings,
                        $overallEnduranceStandings,
                        $overallSprintStandings,
                    );

                    return [
                        'season_id'      => $first->season_id,
                        'series_name'    => $first->series_name,
                        'teams'          => $seasonGroup->unique('entrant_name')->pluck('entrant_name'),
                        'stats'          => $stats,
                        'ordinal'        => $position ? $this->getOrdinalSuffix($position) : '-',
                        'position'       => $position,
                        'sub_class_rows' => $subClassRows,
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
            ->join('season_classes as sc', 'ecl.race_class_id', '=', 'sc.id')
            ->join('season_entries as se', 'ecl.season_entry_id', '=', 'se.id')
            ->join('seasons as s', 'se.season_id', '=', 's.id')
            ->leftJoin('series as ser', 's.series_id', '=', 'ser.id')
            ->leftJoin('entrants as e', 'se.entrant_id', '=', 'e.id')
            ->where('ecd.driver_id', $driverId)
            ->where('ser.world_id', $worldId)
            ->select(['s.id as season_id', 's.year as season_year', 'ser.name as series_name', 'e.name as entrant_name', 'sc.name as class_name']);

        // Results drivers (for one-offs/mid-season)
        $results = DB::table('result_drivers as rd')
            ->join('results as r', 'rd.result_id', '=', 'r.id')
            ->join('entry_cars as ec', 'r.entry_car_id', '=', 'ec.id')
            ->join('entry_classes as ecl', 'ec.entry_class_id', '=', 'ecl.id')
            ->join('season_classes as sc', 'ecl.race_class_id', '=', 'sc.id')
            ->join('season_entries as se', 'ecl.season_entry_id', '=', 'se.id')
            ->join('seasons as s', 'se.season_id', '=', 's.id')
            ->leftJoin('series as ser', 's.series_id', '=', 'ser.id')
            ->leftJoin('entrants as e', 'se.entrant_id', '=', 'e.id')
            ->where('rd.driver_id', $driverId)
            ->where('ser.world_id', $worldId)
            ->select(['s.id as season_id', 's.year as season_year', 'ser.name as series_name', 'e.name as entrant_name', 'sc.name as class_name']);

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

    /**
     * Return per-sub-class career rows for seasons that have sub-classes (e.g. Silver/Gold).
     * When the season mixes endurance and sprint races, appends extra rows for each sub-class
     * filtered to endurance-only and sprint-only results.
     */
    private function getSubClassRows(
        int $driverId,
        int $seasonId,
        string $seriesName,
        string $className,
        Collection $standings,
        Collection $enduranceStandings,
        Collection $sprintStandings,
        Collection $overallEnduranceStandings,
        Collection $overallSprintStandings,
    ): array {
        $subClasses = DB::table('season_classes as sc')
            ->join('entry_classes as ecl', 'ecl.race_class_id', '=', 'sc.id')
            ->join('entry_cars as ec', 'ec.entry_class_id', '=', 'ecl.id')
            ->where('sc.season_id', $seasonId)
            ->whereNotNull('sc.sub_class')
            ->where(function ($q) use ($driverId) {
                $q->whereExists(function ($sq) use ($driverId) {
                    $sq->from('entry_car_driver as ecd')
                       ->whereColumn('ecd.entry_car_id', 'ec.id')
                       ->where('ecd.driver_id', $driverId);
                })->orWhereExists(function ($sq) use ($driverId) {
                    $sq->from('results as r')
                       ->join('result_drivers as rd', 'rd.result_id', '=', 'r.id')
                       ->whereColumn('r.entry_car_id', 'ec.id')
                       ->where('rd.driver_id', $driverId);
                });
            })
            ->select('sc.id as class_id', 'sc.name', 'sc.sub_class', 'sc.display_order')
            ->distinct()
            ->orderBy('sc.display_order')
            ->get()
            ->reject(fn($sc) => strtolower($sc->sub_class) === 'pro');

        if ($subClasses->isEmpty()) {
            return [];
        }

        $hasEnduranceMix = $this->hasEnduranceMix($seasonId);

        $rows = [];

        if (!$hasEnduranceMix) {
            // Standard sub-class rows (no endurance/sprint split)
            foreach ($subClasses as $subClass) {
                $stats    = $this->aggregateStatsForSeasonClass($driverId, $seasonId, $subClass->class_id);
                $position = $standings
                    ->where('season_id', $seasonId)
                    ->where('driver_id', $driverId)
                    ->where('race_class_id', $subClass->class_id)
                    ->first()->rank ?? null;

                $rows[] = [
                    'class_id' => $subClass->class_id,
                    'label'    => $seriesName . ' - ' . $subClass->sub_class,
                    'stats'    => $stats,
                    'ordinal'  => $position ? $this->getOrdinalSuffix($position) : '-',
                    'position' => $position,
                ];
            }

            return $rows;
        }

        // Endurance-mix season: one overall row + per-sub-class cup rows, for each split

        // (Overall) Endurance row
        $oEStats    = $this->aggregateStatsForSeasonFiltered($driverId, $seasonId, 1);
        $oEPosition = $overallEnduranceStandings
            ->where('season_id', $seasonId)
            ->where('driver_id', $driverId)
            ->where('class_name', $className)
            ->first()->rank ?? null;

        $rows[] = [
            'class_id' => null,
            'label'    => '' . $seriesName . ' - Endurance',
            'stats'    => $oEStats,
            'ordinal'  => $oEPosition ? $this->getOrdinalSuffix($oEPosition) : '-',
            'position' => $oEPosition,
        ];

        foreach ($subClasses as $subClass) {
            $eStats    = $this->aggregateStatsForSeasonClassFiltered($driverId, $seasonId, $subClass->class_id, 1);
            $ePosition = $enduranceStandings
                ->where('season_id', $seasonId)
                ->where('driver_id', $driverId)
                ->where('race_class_id', $subClass->class_id)
                ->first()->rank ?? null;

            $rows[] = [
                'class_id' => $subClass->class_id,
                'label'    => $seriesName . ' - Endurance ' . $subClass->sub_class . ' Cup',
                'stats'    => $eStats,
                'ordinal'  => $ePosition ? $this->getOrdinalSuffix($ePosition) : '-',
                'position' => $ePosition,
            ];
        }

        // (Overall) Sprint row
        $oSStats    = $this->aggregateStatsForSeasonFiltered($driverId, $seasonId, 0);
        $oSPosition = $overallSprintStandings
            ->where('season_id', $seasonId)
            ->where('driver_id', $driverId)
            ->where('class_name', $className)
            ->first()->rank ?? null;

        $rows[] = [
            'class_id' => null,
            'label'    => '' . $seriesName . ' - Sprint',
            'stats'    => $oSStats,
            'ordinal'  => $oSPosition ? $this->getOrdinalSuffix($oSPosition) : '-',
            'position' => $oSPosition,
        ];

        foreach ($subClasses as $subClass) {
            $sStats    = $this->aggregateStatsForSeasonClassFiltered($driverId, $seasonId, $subClass->class_id, 0);
            $sPosition = $sprintStandings
                ->where('season_id', $seasonId)
                ->where('driver_id', $driverId)
                ->where('race_class_id', $subClass->class_id)
                ->first()->rank ?? null;

            $rows[] = [
                'class_id' => $subClass->class_id,
                'label'    => $seriesName . ' - Sprint ' . $subClass->sub_class . ' Cup',
                'stats'    => $sStats,
                'ordinal'  => $sPosition ? $this->getOrdinalSuffix($sPosition) : '-',
                'position' => $sPosition,
            ];
        }

        return $rows;
    }

    /**
     * True when a season contains at least one endurance race and at least one non-endurance race.
     */
    private function hasEnduranceMix(int $seasonId): bool
    {
        return DB::table('calendar_races')
                ->where('season_id', $seasonId)->where('endurance', 1)->exists()
            && DB::table('calendar_races')
                ->where('season_id', $seasonId)->where('endurance', 0)->exists();
    }

    /**
     * Aggregate race stats for a driver in a specific season class (sub-class row).
     * Uses class_position for wins/podiums.
     */
    public function aggregateStatsForSeasonClass(int $driverId, int $seasonId, int $classId): object
    {
        $sql = <<<SQL
            SELECT
                COUNT(DISTINCT r.id)                                                               AS races,
                COUNT(DISTINCT CASE WHEN r.sub_class_position = 1 THEN r.id END)              AS wins,
                COUNT(DISTINCT CASE WHEN r.sub_class_position <= 3
                                     AND r.sub_class_position > 0 THEN r.id END)              AS podiums,
                SUM(COALESCE(r.sub_class_points_awarded, r.points_awarded))                   AS total_points,
                COUNT(DISTINCT CASE WHEN r.fastest_lap = 1 THEN r.id END)                    AS fastest_laps,
                (SELECT COUNT(DISTINCT qr2.id)
                 FROM qualifying_results qr2
                 JOIN qualifying_sessions qs2 ON qr2.qualifying_session_id = qs2.id
                 JOIN calendar_races cr2      ON qs2.calendar_race_id      = cr2.id
                 JOIN entry_cars ec2          ON qr2.entry_car_id          = ec2.id
                 JOIN entry_classes ecl2      ON ec2.entry_class_id        = ecl2.id
                 JOIN entry_car_driver ecd2   ON ecd2.entry_car_id         = ec2.id
                 WHERE cr2.season_id        = ?
                   AND ecl2.race_class_id   = ?
                   AND ecd2.driver_id       = ?
                   AND qr2.position         = 1)                                              AS poles
            FROM result_drivers rd
            INNER JOIN results r         ON rd.result_id        = r.id
            INNER JOIN race_sessions rs  ON r.race_session_id   = rs.id
            INNER JOIN calendar_races cr ON rs.calendar_race_id = cr.id
            INNER JOIN entry_cars ec     ON r.entry_car_id      = ec.id
            INNER JOIN entry_classes ecl ON ec.entry_class_id   = ecl.id
            WHERE rd.driver_id       = ?
              AND cr.season_id       = ?
              AND ecl.race_class_id  = ?
              AND rs.name LIKE '%Race%'
        SQL;

        $result = DB::selectOne($sql, [$seasonId, $classId, $driverId, $driverId, $seasonId, $classId]);

        if (!$result || !$result->races) {
            return (object)[
                'races' => 0, 'wins' => 0, 'podiums' => 0,
                'poles' => 0, 'fastest_laps' => 0, 'points' => 0, 'season_active' => 0,
            ];
        }

        return (object)[
            'races'        => (int) $result->races,
            'wins'         => (int) $result->wins,
            'podiums'      => (int) $result->podiums,
            'poles'        => (int) $result->poles,
            'fastest_laps' => (int) $result->fastest_laps,
            'points'       => (fmod((float) $result->total_points, 1) == 0)
                ? (int) $result->total_points
                : (float) $result->total_points,
            'season_active' => 0,
        ];
    }

    /**
     * Aggregate race stats for a driver in a specific season class, filtered to endurance or sprint races only.
     */
    public function aggregateStatsForSeasonClassFiltered(int $driverId, int $seasonId, int $classId, int $endurance): object
    {
        $sql = <<<SQL
            SELECT
                COUNT(DISTINCT r.id)                                                               AS races,
                COUNT(DISTINCT CASE WHEN r.sub_class_position = 1 THEN r.id END)              AS wins,
                COUNT(DISTINCT CASE WHEN r.sub_class_position <= 3
                                     AND r.sub_class_position > 0 THEN r.id END)              AS podiums,
                SUM(COALESCE(r.sub_class_points_awarded, r.points_awarded))                   AS total_points,
                COUNT(DISTINCT CASE WHEN r.fastest_lap = 1 THEN r.id END)                    AS fastest_laps,
                (SELECT COUNT(DISTINCT qr2.id)
                 FROM qualifying_results qr2
                 JOIN qualifying_sessions qs2 ON qr2.qualifying_session_id = qs2.id
                 JOIN calendar_races cr2      ON qs2.calendar_race_id      = cr2.id
                 JOIN entry_cars ec2          ON qr2.entry_car_id          = ec2.id
                 JOIN entry_classes ecl2      ON ec2.entry_class_id        = ecl2.id
                 JOIN entry_car_driver ecd2   ON ecd2.entry_car_id         = ec2.id
                 WHERE cr2.season_id      = ?
                   AND ecl2.race_class_id = ?
                   AND ecd2.driver_id     = ?
                   AND cr2.endurance      = ?
                   AND qr2.position       = 1)                                                AS poles
            FROM result_drivers rd
            INNER JOIN results r         ON rd.result_id        = r.id
            INNER JOIN race_sessions rs  ON r.race_session_id   = rs.id
            INNER JOIN calendar_races cr ON rs.calendar_race_id = cr.id
            INNER JOIN entry_cars ec     ON r.entry_car_id      = ec.id
            INNER JOIN entry_classes ecl ON ec.entry_class_id   = ecl.id
            WHERE rd.driver_id      = ?
              AND cr.season_id      = ?
              AND ecl.race_class_id = ?
              AND cr.endurance      = ?
              AND rs.name LIKE '%Race%'
        SQL;

        $result = DB::selectOne($sql, [$seasonId, $classId, $driverId, $endurance, $driverId, $seasonId, $classId, $endurance]);

        if (!$result || !$result->races) {
            return (object)[
                'races' => 0, 'wins' => 0, 'podiums' => 0,
                'poles' => 0, 'fastest_laps' => 0, 'points' => 0, 'season_active' => 0,
            ];
        }

        return (object)[
            'races'        => (int) $result->races,
            'wins'         => (int) $result->wins,
            'podiums'      => (int) $result->podiums,
            'poles'        => (int) $result->poles,
            'fastest_laps' => (int) $result->fastest_laps,
            'points'       => (fmod((float) $result->total_points, 1) == 0)
                ? (int) $result->total_points
                : (float) $result->total_points,
            'season_active' => 0,
        ];
    }

    /**
     * Championship standings filtered to endurance or sprint races only.
     */
    private function calculateChampionshipStandingsFiltered(int $endurance): Collection
    {
        return collect(DB::select("
            SELECT season_id, driver_id, race_class_id, rank FROM (
                SELECT
                    cr.season_id,
                    rd.driver_id,
                    ecl.race_class_id,
                    DENSE_RANK() OVER (
                        PARTITION BY cr.season_id, ecl.race_class_id
                        ORDER BY SUM(COALESCE(r.sub_class_points_awarded, r.points_awarded)) DESC,
                                 SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
                    ) as rank
                FROM result_drivers rd
                JOIN results r         ON rd.result_id        = r.id
                JOIN race_sessions rs  ON r.race_session_id   = rs.id
                JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                JOIN entry_cars ec     ON r.entry_car_id      = ec.id
                JOIN entry_classes ecl ON ec.entry_class_id   = ecl.id
                WHERE rs.name LIKE '%Race%'
                  AND cr.endurance = {$endurance}
                GROUP BY cr.season_id, rd.driver_id, ecl.race_class_id
            ) as standings
        "));
    }

    /**
     * Overall class standings filtered to endurance or sprint races only.
     * Pools all sub-classes with the same class name, keyed by class_name.
     */
    private function calculateOverallClassStandingsFiltered(int $endurance): Collection
    {
        return collect(DB::select("
            SELECT season_id, driver_id, class_name, rank FROM (
                SELECT
                    cr.season_id,
                    rd.driver_id,
                    sc.name AS class_name,
                    DENSE_RANK() OVER (
                        PARTITION BY cr.season_id, sc.name
                        ORDER BY SUM(r.points_awarded) DESC, SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
                    ) as rank
                FROM result_drivers rd
                JOIN results r         ON rd.result_id        = r.id
                JOIN race_sessions rs  ON r.race_session_id   = rs.id
                JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                JOIN entry_cars ec     ON r.entry_car_id      = ec.id
                JOIN entry_classes ecl ON ec.entry_class_id   = ecl.id
                JOIN season_classes sc ON ecl.race_class_id   = sc.id
                WHERE rs.name LIKE '%Race%'
                  AND cr.endurance = {$endurance}
                GROUP BY cr.season_id, rd.driver_id, sc.name
            ) as overall_standings
        "));
    }

    /**
     * Aggregate race stats for a driver in a season, filtered to endurance or sprint races only.
     * Uses class_position for wins/podiums (combines all sub-classes).
     */
    public function aggregateStatsForSeasonFiltered(int $driverId, int $seasonId, int $endurance): object
    {
        $sql = <<<SQL
            SELECT
                SUM(season_car_stats.races)        AS races,
                SUM(season_car_stats.wins)         AS wins,
                SUM(season_car_stats.podiums)      AS podiums,
                SUM(season_car_stats.fastest_laps) AS fastest_laps,
                SUM(season_car_stats.total_points) AS total_points,
                SUM(season_car_stats.poles)        AS poles
            FROM (
                SELECT
                    COUNT(DISTINCT r.id)                                                         AS races,
                    COUNT(DISTINCT CASE WHEN r.class_position = 1 THEN r.id END)                AS wins,
                    COUNT(DISTINCT CASE WHEN r.class_position <= 3
                                         AND r.class_position > 0 THEN r.id END)               AS podiums,
                    SUM(r.points_awarded)                                                        AS total_points,
                    COUNT(DISTINCT CASE WHEN r.fastest_lap = 1 THEN r.id END)                  AS fastest_laps,
                    (SELECT COUNT(DISTINCT qr.id)
                     FROM qualifying_results qr
                     JOIN qualifying_sessions qs ON qr.qualifying_session_id = qs.id
                     JOIN calendar_races cr2      ON qs.calendar_race_id      = cr2.id
                     WHERE cr2.season_id  = cr.season_id
                       AND cr2.endurance  = ?
                       AND qr.position    = 1
                       AND qr.entry_car_id = r.entry_car_id) AS poles
                FROM result_drivers rd
                INNER JOIN results r         ON rd.result_id        = r.id
                INNER JOIN race_sessions rs  ON r.race_session_id   = rs.id
                INNER JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                WHERE rd.driver_id  = ?
                  AND cr.season_id  = ?
                  AND cr.endurance  = ?
                  AND rs.name LIKE '%Race%'
                GROUP BY cr.season_id, r.entry_car_id
            ) AS season_car_stats
        SQL;

        $result = DB::selectOne($sql, [$endurance, $driverId, $seasonId, $endurance]);

        if (!$result || !$result->races) {
            return (object)[
                'races' => 0, 'wins' => 0, 'podiums' => 0,
                'poles' => 0, 'fastest_laps' => 0, 'points' => 0, 'season_active' => 0,
            ];
        }

        return (object)[
            'races'        => (int) $result->races,
            'wins'         => (int) $result->wins,
            'podiums'      => (int) $result->podiums,
            'poles'        => (int) $result->poles,
            'fastest_laps' => (int) $result->fastest_laps,
            'points'       => (fmod((float) $result->total_points, 1) == 0)
                ? (int) $result->total_points
                : (float) $result->total_points,
            'season_active' => 0,
        ];
    }

    /**
     * Overall class standings — pools all sub-classes sharing the same season_class name,
     * ranked by points_awarded (class_position based). Used for the main career row position.
     */
    private function calculateOverallClassStandings(): Collection
    {
        return collect(DB::select("
            SELECT season_id, driver_id, class_name, rank FROM (
                SELECT
                    cr.season_id,
                    rd.driver_id,
                    sc.name AS class_name,
                    DENSE_RANK() OVER (
                        PARTITION BY cr.season_id, sc.name
                        ORDER BY SUM(r.points_awarded) DESC, SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
                    ) as rank
                FROM result_drivers rd
                JOIN results r         ON rd.result_id        = r.id
                JOIN race_sessions rs  ON r.race_session_id   = rs.id
                JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                JOIN entry_cars ec     ON r.entry_car_id      = ec.id
                JOIN entry_classes ecl ON ec.entry_class_id   = ecl.id
                JOIN season_classes sc ON ecl.race_class_id   = sc.id
                WHERE rs.name LIKE '%Race%'
                GROUP BY cr.season_id, rd.driver_id, sc.name
            ) as overall_standings
        "));
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
                        ORDER BY SUM(COALESCE(r.sub_class_points_awarded, r.points_awarded)) DESC, SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
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
