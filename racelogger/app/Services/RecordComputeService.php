<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecordComputeService
{
    private const LIMIT = 10;
    private const MIN_RACES_FOR_PCT = 20;  // minimum entries to appear in % rankings

    // ─────────────────────────────────────────────────────────────────────────
    // Entry point
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compute all records for the given world and return as a structured array.
     */
    public function compute(int $worldId): array
    {
        $races   = $this->loadRaceData($worldId);
        $quali   = $this->loadQualiData($worldId);
        $raceSeq = $this->buildRaceSeq($races);

        $poles      = $quali->filter(fn($r) => (int) $r->position === 1)
                            ->unique(fn($r) => $r->driver_id . '_' . $r->race_id);
        $fastestLaps = $races->filter(fn($r) => (int) $r->fastest_lap === 1);
        $podiums     = $races->filter(fn($r) => (int) $r->class_position >= 1 && (int) $r->class_position <= 3);

        return [
            'entries'       => $this->computeEntries($races, $raceSeq),
            'wins'          => $this->computeWins($races, $raceSeq),
            'poles'         => $this->genericSection($poles, $races, $raceSeq),
            'fastest_laps'  => $this->genericSection($fastestLaps, $races, $raceSeq),
            'podiums'       => $this->genericSection($podiums, $races, $raceSeq),
            'points'        => $this->computePoints($races, $raceSeq),
            'race_finishes' => $this->computeRaceFinishes($races, $raceSeq),
            'championships' => $this->computeChampionships($worldId),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data loaders
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load all main race results for the world.
     * Each row = one driver's participation in one race.
     */
    private function loadRaceData(int $worldId): Collection
    {
        return DB::table('result_drivers as rd')
            ->join('results as r',          'rd.result_id',         '=', 'r.id')
            ->join('race_sessions as rs',   'r.race_session_id',    '=', 'rs.id')
            ->join('calendar_races as cr',  'rs.calendar_race_id',  '=', 'cr.id')
            ->join('seasons as s',          'cr.season_id',         '=', 's.id')
            ->join('series as ser',         's.series_id',          '=', 'ser.id')
            ->join('drivers as d',          'rd.driver_id',         '=', 'd.id')
            ->join('entry_cars as ec',      'r.entry_car_id',       '=', 'ec.id')
            ->join('entry_classes as ecl',  'ec.entry_class_id',    '=', 'ecl.id')
            ->join('season_entries as se',  'ecl.season_entry_id',  '=', 'se.id')
            ->join('constructors as con',   'se.constructor_id',    '=', 'con.id')
            ->where('ser.world_id', $worldId)
            ->where('rs.is_sprint', 0)
            ->select([
                'rd.driver_id',
                'd.first_name', 'd.last_name', 'd.date_of_birth',
                'cr.id as race_id', 'cr.race_date', 'cr.gp_name', 'cr.race_code',
                's.id as season_id', 's.year as season_year',
                'se.constructor_id', 'con.name as constructor_name',
                'r.class_position', 'r.status', 'r.points_awarded', 'r.fastest_lap',
            ])
            ->orderBy('cr.race_date')
            ->get();
    }

    /**
     * Load all qualifying data (for pole position stats).
     */
    private function loadQualiData(int $worldId): Collection
    {
        return DB::table('qualifying_results as qr')
            ->join('qualifying_sessions as qs', 'qr.qualifying_session_id', '=', 'qs.id')
            ->join('calendar_races as cr',       'qs.calendar_race_id',       '=', 'cr.id')
            ->join('seasons as s',               'cr.season_id',              '=', 's.id')
            ->join('series as ser',              's.series_id',               '=', 'ser.id')
            ->join('entry_car_driver as ecd',    'qr.entry_car_id',           '=', 'ecd.entry_car_id')
            ->join('drivers as d',               'ecd.driver_id',             '=', 'd.id')
            ->where('ser.world_id', $worldId)
            ->select([
                'ecd.driver_id',
                'd.first_name', 'd.last_name', 'd.date_of_birth',
                'cr.id as race_id', 'cr.race_date', 'cr.gp_name', 'cr.race_code',
                's.id as season_id', 's.year as season_year',
                'qr.position',
            ])
            ->orderBy('cr.race_date')
            ->get();
    }

    /**
     * Build a map of race_id → sequential race number (1, 2, 3…)
     * used for consecutive streak calculations.
     */
    private function buildRaceSeq(Collection $races): array
    {
        return $races->pluck('race_id')
            ->unique()
            ->values()
            ->mapWithKeys(fn($id, $i) => [$id => $i + 1])
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section builders
    // ─────────────────────────────────────────────────────────────────────────

    private function computeEntries(Collection $races, array $raceSeq): array
    {
        $starts = $races->filter(fn($r) => $r->status !== 'dns');

        return [
            'total'               => $this->statTotal($races),
            'youngest'            => $this->statYoungest($races),
            'oldest'              => $this->statOldest($races),
            'consecutive_entries' => $this->statConsecutive($races, $raceSeq),
            'consecutive_starts'  => $this->statConsecutive($starts, $raceSeq),
            'one_constructor'     => $this->racesOneConstructor($races),
        ];
    }

    private function computeWins(Collection $races, array $raceSeq): array
    {
        $wins = $races->filter(fn($r) => (int) $r->class_position === 1);

        return [
            'total'             => $this->statTotal($wins),
            'percentage'        => $this->statPercentage($wins, $races),
            'single_constructor'=> $this->winsSingleConstructor($wins),
            'in_season'         => $this->statInSeason($wins),
            'pct_in_season'     => $this->statPctInSeason($wins, $races),
            'consecutive'       => $this->statConsecutive($wins, $raceSeq),
            'first_season'      => $this->mostWinsFirstSeason($races, $wins),
            'youngest'          => $this->statYoungest($wins),
            'oldest'            => $this->statOldest($wins),
            'races_before_win'  => $this->racesBeforeFirstWin($races, $wins),
            'without_win'       => $this->racesWithoutWin($races, $wins),
            'at_same_gp'        => $this->statAtSameGP($wins),
        ];
    }

    /**
     * Generic section for poles, fastest laps, and podiums —
     * all share the same set of sub-records.
     */
    private function genericSection(Collection $hits, Collection $allRaces, array $raceSeq): array
    {
        return [
            'total'         => $this->statTotal($hits),
            'percentage'    => $this->statPercentage($hits, $allRaces),
            'in_season'     => $this->statInSeason($hits),
            'pct_in_season' => $this->statPctInSeason($hits, $allRaces),
            'consecutive'   => $this->statConsecutive($hits, $raceSeq),
            'youngest'      => $this->statYoungest($hits),
            'oldest'        => $this->statOldest($hits),
            'at_same_gp'    => $this->statAtSameGP($hits),
        ];
    }

    private function computePoints(Collection $races, array $raceSeq): array
    {
        $scoring = $races->filter(fn($r) => (float) $r->points_awarded > 0);

        return [
            'total'         => $this->totalPoints($races),
            'percentage'    => $this->statPercentage($scoring, $races),
            'in_season'     => $this->mostPointsInSeason($races),
            'pct_in_season' => $this->statPctInSeason($scoring, $races),
            'consecutive'   => $this->statConsecutive($scoring, $raceSeq),
            'youngest'      => $this->statYoungest($scoring),
            'oldest'        => $this->statOldest($scoring),
            'at_same_gp'    => $this->mostPointsAtSameGP($races),
        ];
    }

    private function computeRaceFinishes(Collection $races, array $raceSeq): array
    {
        $finishes = $races->filter(fn($r) => $r->status === 'finished');

        return [
            'total'       => $this->statTotal($finishes),
            'consecutive' => $this->statConsecutive($finishes, $raceSeq),
        ];
    }

    private function computeChampionships(int $worldId): array
    {
        // Get all championship wins (rank=1 in completed seasons) via SQL window function
        $wins = collect(DB::select("
            SELECT sub.driver_id, d.first_name, d.last_name, d.date_of_birth,
                   sub.season_id, sub.season_year, lr.last_race_date
            FROM (
                SELECT rd.driver_id, cr.season_id, s.year as season_year,
                       DENSE_RANK() OVER (
                           PARTITION BY cr.season_id
                           ORDER BY SUM(r.points_awarded) DESC,
                                    SUM(CASE WHEN r.class_position = 1 THEN 1 ELSE 0 END) DESC
                       ) as rnk
                FROM result_drivers rd
                JOIN results r        ON rd.result_id = r.id
                JOIN race_sessions rs  ON r.race_session_id = rs.id
                JOIN calendar_races cr ON rs.calendar_race_id = cr.id
                JOIN seasons s         ON cr.season_id = s.id
                JOIN series ser        ON s.series_id = ser.id
                WHERE ser.world_id = ?
                  AND rs.name LIKE '%Race%'
                GROUP BY rd.driver_id, cr.season_id, s.year
            ) sub
            JOIN drivers d ON sub.driver_id = d.id
            JOIN (
                SELECT season_id, MAX(race_date) as last_race_date
                FROM calendar_races GROUP BY season_id
            ) lr ON lr.season_id = sub.season_id
            WHERE sub.rnk = 1
            ORDER BY lr.last_race_date
        ", [$worldId]));

        // ── Total championships ──────────────────────────────────────────────
        $total = [];
        foreach ($wins->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            $total[] = [
                'name'  => $first->first_name . ' ' . $first->last_name,
                'value' => (string) $rows->count(),
                'extra' => $rows->pluck('season_year')->join(', '),
            ];
        }
        usort($total, fn($a, $b) => (int) $b['value'] <=> (int) $a['value']);
        $total = array_slice($total, 0, self::LIMIT);

        // ── Youngest champion (age at first title) ───────────────────────────
        $youngest = [];
        foreach ($wins->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            if (!$first->date_of_birth) continue;
            $firstWin = $rows->sortBy('last_race_date')->first();
            $days = $this->ageDays($first->date_of_birth, $firstWin->last_race_date);
            $youngest[] = [
                'name'      => $first->first_name . ' ' . $first->last_name,
                'value_raw' => $days,
                'value'     => $this->formatAge($days),
                'extra'     => (string) $firstWin->season_year,
            ];
        }
        usort($youngest, fn($a, $b) => $a['value_raw'] <=> $b['value_raw']);
        $youngest = array_map(
            fn($r) => ['name' => $r['name'], 'value' => $r['value'], 'extra' => $r['extra']],
            array_slice($youngest, 0, self::LIMIT)
        );

        // ── Oldest champion (age at last title) ──────────────────────────────
        $oldest = [];
        foreach ($wins->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            if (!$first->date_of_birth) continue;
            $lastWin = $rows->sortByDesc('last_race_date')->first();
            $days = $this->ageDays($first->date_of_birth, $lastWin->last_race_date);
            $oldest[] = [
                'name'      => $first->first_name . ' ' . $first->last_name,
                'value_raw' => $days,
                'value'     => $this->formatAge($days),
                'extra'     => (string) $lastWin->season_year,
            ];
        }
        usort($oldest, fn($a, $b) => $b['value_raw'] <=> $a['value_raw']);
        $oldest = array_map(
            fn($r) => ['name' => $r['name'], 'value' => $r['value'], 'extra' => $r['extra']],
            array_slice($oldest, 0, self::LIMIT)
        );

        return compact('total', 'youngest', 'oldest');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared stat helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Most appearances (count of distinct races). */
    private function statTotal(Collection $hits): array
    {
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            $data[] = [
                'name'  => $first->first_name . ' ' . $first->last_name,
                'value' => $rows->pluck('race_id')->unique()->count(),
                'extra' => null,
            ];
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Achievement % of total race entries. Requires MIN_RACES_FOR_PCT entries. */
    private function statPercentage(Collection $hits, Collection $allRaces): array
    {
        $totals = [];
        foreach ($allRaces->groupBy('driver_id') as $driverId => $rows) {
            $totals[$driverId] = $rows->pluck('race_id')->unique()->count();
        }

        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $total = $totals[$driverId] ?? 0;
            if ($total < self::MIN_RACES_FOR_PCT) continue;
            $count = $rows->pluck('race_id')->unique()->count();
            $first = $rows->first();
            $data[] = [
                'name'  => $first->first_name . ' ' . $first->last_name,
                'value' => round(100.0 * $count / $total, 2),
                'extra' => "{$count}/{$total}",
            ];
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => number_format($r['value'], 2) . '%', 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Most achievements in a single season. */
    private function statInSeason(Collection $hits): array
    {
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first      = $rows->first();
            $best       = 0;
            $bestYear   = null;
            foreach ($rows->groupBy('season_id') as $seasonRows) {
                $count = $seasonRows->pluck('race_id')->unique()->count();
                if ($count > $best) { $best = $count; $bestYear = $seasonRows->first()->season_year; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => (string) $bestYear];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Highest achievement % in a single season (min 5 races in that season). */
    private function statPctInSeason(Collection $hits, Collection $allRaces): array
    {
        $entryMap = [];
        foreach ($allRaces->groupBy('driver_id') as $driverId => $driverRaces) {
            foreach ($driverRaces->groupBy('season_id') as $seasonId => $seasonRaces) {
                $entryMap[$driverId][$seasonId] = $seasonRaces->pluck('race_id')->unique()->count();
            }
        }

        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first    = $rows->first();
            $bestPct  = 0;
            $bestYear = null;
            foreach ($rows->groupBy('season_id') as $seasonId => $seasonRows) {
                $total = $entryMap[$driverId][$seasonId] ?? 0;
                if ($total < 5) continue;
                $pct = round(100.0 * $seasonRows->pluck('race_id')->unique()->count() / $total, 2);
                if ($pct > $bestPct) { $bestPct = $pct; $bestYear = $seasonRows->first()->season_year; }
            }
            if ($bestPct > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $bestPct, 'extra' => (string) $bestYear];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => number_format($r['value'], 2) . '%', 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Longest consecutive-race streak with this achievement. */
    private function statConsecutive(Collection $hits, array $raceSeq): array
    {
        $seqToRace = array_flip($raceSeq);
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            $nums  = $rows->pluck('race_id')->unique()
                ->map(fn($id) => $raceSeq[$id] ?? null)
                ->filter()->sort()->values()->toArray();
            $streak = $this->longestStreakWithBounds($nums);
            if ($streak['length'] > 0) {
                $startRow = isset($seqToRace[$streak['start']])
                    ? $rows->first(fn($r) => $r->race_id === $seqToRace[$streak['start']])
                    : null;
                $endRow = isset($seqToRace[$streak['end']])
                    ? $rows->first(fn($r) => $r->race_id === $seqToRace[$streak['end']])
                    : null;
                $extra = ($startRow && $endRow)
                    ? $startRow->race_code . ' ' . $startRow->season_year . ' – ' . $endRow->race_code . ' ' . $endRow->season_year
                    : null;
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $streak['length'], 'extra' => $extra];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Youngest driver to achieve this (age at first occurrence). */
    private function statYoungest(Collection $hits): array
    {
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            if (!$first->date_of_birth) continue;
            $earliest = $rows->sortBy('race_date')->first();
            $days     = $this->ageDays($first->date_of_birth, $earliest->race_date);
            $data[]   = [
                'name'      => $first->first_name . ' ' . $first->last_name,
                'value_raw' => $days,
                'value'     => $this->formatAge($days),
                'extra'     => (string) $earliest->season_year,
            ];
        }
        usort($data, fn($a, $b) => $a['value_raw'] <=> $b['value_raw']);
        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => $r['value'], 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Oldest driver to achieve this (age at last occurrence). */
    private function statOldest(Collection $hits): array
    {
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first  = $rows->first();
            if (!$first->date_of_birth) continue;
            $latest = $rows->sortByDesc('race_date')->first();
            $days   = $this->ageDays($first->date_of_birth, $latest->race_date);
            $data[] = [
                'name'      => $first->first_name . ' ' . $first->last_name,
                'value_raw' => $days,
                'value'     => $this->formatAge($days),
                'extra'     => (string) $latest->season_year,
            ];
        }
        usort($data, fn($a, $b) => $b['value_raw'] <=> $a['value_raw']);
        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => $r['value'], 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Most achievements at the same GP name. */
    private function statAtSameGP(Collection $hits): array
    {
        $data = [];
        foreach ($hits->groupBy('driver_id') as $driverId => $rows) {
            $first    = $rows->first();
            $best     = 0;
            $bestGP   = null;
            foreach ($rows->groupBy('gp_name') as $gp => $gpRows) {
                $count = $gpRows->pluck('race_id')->unique()->count();
                if ($count > $best) { $best = $count; $bestGP = $gp; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => $bestGP];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section-specific helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Most races with a single constructor. */
    private function racesOneConstructor(Collection $races): array
    {
        $data = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            $best  = 0; $bestName = null;
            foreach ($rows->groupBy('constructor_id') as $conId => $conRows) {
                $count = $conRows->pluck('race_id')->unique()->count();
                if ($count > $best) { $best = $count; $bestName = $conRows->first()->constructor_name; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => $bestName];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Most wins with a single constructor. */
    private function winsSingleConstructor(Collection $wins): array
    {
        $data = [];
        foreach ($wins->groupBy('driver_id') as $driverId => $rows) {
            $first = $rows->first();
            $best  = 0; $bestName = null;
            foreach ($rows->groupBy('constructor_id') as $conId => $conRows) {
                $count = $conRows->pluck('race_id')->unique()->count();
                if ($count > $best) { $best = $count; $bestName = $conRows->first()->constructor_name; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => $bestName];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Most wins in the driver's first season. */
    private function mostWinsFirstSeason(Collection $races, Collection $wins): array
    {
        // Map each driver to their first season id
        $firstSeasonMap = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            $firstSeasonMap[$driverId] = $rows->sortBy('race_date')->first()->season_id;
        }

        $data = [];
        foreach ($wins->groupBy('driver_id') as $driverId => $rows) {
            $firstSeason = $firstSeasonMap[$driverId] ?? null;
            if (!$firstSeason) continue;
            $firstSeasonWins = $rows->filter(fn($r) => $r->season_id === $firstSeason);
            $count = $firstSeasonWins->pluck('race_id')->unique()->count();
            if ($count > 0) {
                $first = $rows->first();
                $data[] = [
                    'name'  => $first->first_name . ' ' . $first->last_name,
                    'value' => $count,
                    'extra' => (string) $firstSeasonWins->first()->season_year,
                ];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Most races entered before recording first win. */
    private function racesBeforeFirstWin(Collection $races, Collection $wins): array
    {
        $data = [];
        foreach ($wins->pluck('driver_id')->unique() as $driverId) {
            $firstWinDate = $wins->where('driver_id', $driverId)->sortBy('race_date')->first()->race_date;
            $driverRaces  = $races->where('driver_id', $driverId);
            $count        = $driverRaces->filter(fn($r) => $r->race_date < $firstWinDate)
                                        ->pluck('race_id')->unique()->count();
            $first = $driverRaces->first();
            $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $count, 'extra' => null];
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Most races entered by a driver who never won. */
    private function racesWithoutWin(Collection $races, Collection $wins): array
    {
        $winnerIds = $wins->pluck('driver_id')->unique()->toArray();
        $data = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            if (in_array($driverId, $winnerIds)) continue;
            $first = $rows->first();
            $data[] = [
                'name'  => $first->first_name . ' ' . $first->last_name,
                'value' => $rows->pluck('race_id')->unique()->count(),
                'extra' => null,
            ];
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);
        return $this->finalize($data);
    }

    /** Total career points. */
    private function totalPoints(Collection $races): array
    {
        $data = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            $total = $rows->sum(fn($r) => (float) $r->points_awarded);
            if ($total <= 0) continue;
            $first  = $rows->first();
            $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $total, 'extra' => null];
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => $this->formatPoints($r['value']), 'extra' => null],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Most points in a single season. */
    private function mostPointsInSeason(Collection $races): array
    {
        $data = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            $first    = $rows->first();
            $best     = 0;
            $bestYear = null;
            foreach ($rows->groupBy('season_id') as $seasonRows) {
                $pts = $seasonRows->sum(fn($r) => (float) $r->points_awarded);
                if ($pts > $best) { $best = $pts; $bestYear = $seasonRows->first()->season_year; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => (string) $bestYear];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => $this->formatPoints($r['value']), 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    /** Most total points at the same GP (across all years). */
    private function mostPointsAtSameGP(Collection $races): array
    {
        $data = [];
        foreach ($races->groupBy('driver_id') as $driverId => $rows) {
            $first  = $rows->first();
            $best   = 0;
            $bestGP = null;
            foreach ($rows->groupBy('gp_name') as $gp => $gpRows) {
                $pts = $gpRows->sum(fn($r) => (float) $r->points_awarded);
                if ($pts > $best) { $best = $pts; $bestGP = $gp; }
            }
            if ($best > 0) {
                $data[] = ['name' => $first->first_name . ' ' . $first->last_name, 'value' => $best, 'extra' => $bestGP];
            }
        }
        usort($data, fn($a, $b) => $b['value'] <=> $a['value']);

        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => $this->formatPoints($r['value']), 'extra' => $r['extra']],
            array_slice($data, 0, self::LIMIT)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utility
    // ─────────────────────────────────────────────────────────────────────────

    /** Longest consecutive-number streak in a sorted array. */
    private function longestStreak(array $nums): int
    {
        return $this->longestStreakWithBounds($nums)['length'];
    }

    /**
     * Longest consecutive-number streak with the start and end sequence values.
     * Returns ['length' => int, 'start' => int|null, 'end' => int|null].
     */
    private function longestStreakWithBounds(array $nums): array
    {
        if (empty($nums)) return ['length' => 0, 'start' => null, 'end' => null];
        $maxLen = $curLen = 1;
        $maxEnd = $curEnd = 0;
        for ($i = 1; $i < count($nums); $i++) {
            if ($nums[$i] === $nums[$i - 1] + 1) {
                $curLen++;
                $curEnd = $i;
            } else {
                $curLen = 1;
                $curEnd = $i;
            }
            if ($curLen > $maxLen) {
                $maxLen = $curLen;
                $maxEnd = $curEnd;
            }
        }
        $maxStart = $maxEnd - $maxLen + 1;
        return ['length' => $maxLen, 'start' => $nums[$maxStart], 'end' => $nums[$maxEnd]];
    }

    private function ageDays(string $dob, string $date): int
    {
        return (int) Carbon::parse($dob)->diffInDays(Carbon::parse($date));
    }

    private function formatAge(int $days): string
    {
        $years = (int) floor($days / 365.25);
        $rem   = $days - (int) round($years * 365.25);
        return "{$years}y {$rem}d";
    }

    private function formatPoints(float $pts): string
    {
        return fmod($pts, 1) == 0 ? (string) (int) $pts : number_format($pts, 1);
    }

    /**
     * Convert intermediate array to top-10 display format.
     * Expects rows already sorted; converts `value` to string.
     */
    private function finalize(array $data): array
    {
        return array_map(
            fn($r) => ['name' => $r['name'], 'value' => (string) $r['value'], 'extra' => $r['extra'] ?? null],
            array_slice($data, 0, self::LIMIT)
        );
    }
}
