<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CareerResultsGridService
{
    /**
     * Return per-race results for a driver, grouped by series then season.
     *
     * @param int $driverId
     * @return array<string, array{series_name: string, is_multiclass: bool, is_spec: bool, seasons: array}>
     */
    public function getResultsGrid(int $driverId, int $worldId): array
    {
        $results = $this->fetchRaceResults($driverId, $worldId);

        if ($results->isEmpty()) {
            return [];
        }

        $seasonIds = $results->pluck('season_id')->unique()->values()->all();
        $calendars = $this->fetchCalendars($seasonIds);

        return $this->buildGrid($results, $calendars);
    }

    /**
     * Fetch all race results for the driver with entry and car detail.
     */
    private function fetchRaceResults(int $driverId, int $worldId): Collection
    {
        return DB::table('result_drivers as rd')
            ->join('results as r',          'rd.result_id',          '=', 'r.id')
            ->join('race_sessions as rs',   'r.race_session_id',     '=', 'rs.id')
            ->join('calendar_races as cr',  'rs.calendar_race_id',   '=', 'cr.id')
            ->join('seasons as s',          'cr.season_id',          '=', 's.id')
            ->join('series as ser',         's.series_id',           '=', 'ser.id')
            ->join('entry_cars as ec',      'r.entry_car_id',        '=', 'ec.id')
            ->join('entry_classes as ecl',  'ec.entry_class_id',     '=', 'ecl.id')
            ->join('season_entries as se',  'ecl.season_entry_id',   '=', 'se.id')
            ->join('entrants as e',         'se.entrant_id',         '=', 'e.id')
            ->join('car_models as cm',      'ec.car_model_id',       '=', 'cm.id')
            ->leftJoin('engines as eng',        'cm.engine_id',          '=', 'eng.id')
            ->leftJoin('season_classes as sc',  'ecl.race_class_id',     '=', 'sc.id')
            ->where('rd.driver_id', $driverId)
            ->where('ser.world_id', $worldId)
            ->where('rs.name', 'LIKE', '%Race%')
            ->select([
                's.id as season_id',
                's.year as season_year',
                'ser.id as series_id',
                'ser.name as series_name',
                'ser.short_name as series_short_name',
                'ser.is_multiclass',
                'cr.round_number',
                'cr.race_code',
                'rs.id as session_id',
                'rs.is_sprint',
                'r.position',
                'r.class_position',
                'r.status',
                'r.points_awarded',
                'ec.id as entry_car_id',
                'e.name as entrant_name',
                'cm.name as car_model_name',
                'eng.name as engine_name',
                'eng.capacity as engine_capacity',
                'eng.configuration as engine_configuration',
                'sc.name as class_name',
                'ecl.id as entry_class_id',
            ])
            ->orderBy('s.year')
            ->orderBy('cr.round_number')
            ->orderBy('rs.session_order')
            ->get();
    }

    /**
     * Fetch the full calendar for the given seasons, including rounds that have
     * no race sessions yet (upcoming races). Returns a Collection keyed by season_id,
     * where each item has round_number, race_code, and sessions[].
     *
     * @param int[] $seasonIds
     */
    private function fetchCalendars(array $seasonIds): Collection
    {
        // All calendar rounds (including future rounds with no sessions yet)
        $races = DB::table('calendar_races')
            ->whereIn('season_id', $seasonIds)
            ->select(['season_id', 'round_number', 'race_code'])
            ->orderBy('round_number')
            ->get()
            ->groupBy('season_id');

        // Race sessions that exist so far
        $sessions = DB::table('race_sessions as rs')
            ->join('calendar_races as cr', 'rs.calendar_race_id', '=', 'cr.id')
            ->whereIn('cr.season_id', $seasonIds)
            ->where('rs.name', 'LIKE', '%Race%')
            ->select(['cr.season_id', 'cr.round_number', 'rs.id as session_id', 'rs.is_sprint', 'rs.session_order'])
            ->orderBy('rs.session_order')
            ->get()
            ->groupBy(['season_id', 'round_number']);

        // Merge: every race gets its sessions (or an empty array)
        $merged = collect();

        foreach ($races as $seasonId => $seasonRaces) {
            $seasonSessions = $sessions->get($seasonId, collect());

            foreach ($seasonRaces as $race) {
                $roundSessions = $seasonSessions->get($race->round_number, collect());

                if ($roundSessions->isEmpty()) {
                    $merged->push((object) [
                        'season_id'    => $seasonId,
                        'round_number' => $race->round_number,
                        'race_code'    => $race->race_code,
                        'session_id'   => null,
                        'is_sprint'    => null,
                    ]);
                } else {
                    foreach ($roundSessions as $session) {
                        $merged->push((object) [
                            'season_id'    => $seasonId,
                            'round_number' => $race->round_number,
                            'race_code'    => $race->race_code,
                            'session_id'   => $session->session_id,
                            'is_sprint'    => $session->is_sprint,
                        ]);
                    }
                }
            }
        }

        return $merged->groupBy('season_id');
    }

    /**
     * Build the grid structure: series → seasons → entries → results.
     */
    private function buildGrid(Collection $results, Collection $calendars): array
    {
        $grid = [];

        foreach ($results->groupBy('series_id') as $seriesResults) {
            $first      = $seriesResults->first();
            $seriesName = $first->series_name;
            $isMulticlass = (bool) $first->is_multiclass;
            $isSpec       = ($first->series_short_name === 'F2');

            $seasons = [];

            foreach ($seriesResults->groupBy('season_id') as $seasonId => $seasonResults) {
                $seasonFirst = $seasonResults->first();

                // Build ordered calendar: round_number → { race_code, sessions[] }
                // sessions[] is empty for rounds not yet played
                $calendar = [];
                foreach ($calendars->get($seasonId, collect()) as $row) {
                    $round = $row->round_number;
                    if (!isset($calendar[$round])) {
                        $calendar[$round] = [
                            'race_code' => $row->race_code,
                            'sessions'  => [],
                        ];
                    }
                    if ($row->session_id !== null) {
                        $calendar[$round]['sessions'][] = [
                            'session_id' => $row->session_id,
                            'is_sprint'  => (bool) $row->is_sprint,
                        ];
                    }
                }
                ksort($calendar);

                // Build per-entry data (one entry per entry_class / car stint)
                $entries = [];
                foreach ($seasonResults->groupBy('entry_class_id') as $entryResults) {
                    $ef = $entryResults->first();

                    // result map: round_number → session_id → display string
                    $resultMap = [];
                    foreach ($entryResults as $res) {
                        $resultMap[$res->round_number][$res->session_id] = $this->formatResult($res);
                    }

                    $entries[] = [
                        'entrant' => $ef->entrant_name,
                        'class'   => $ef->class_name ?? '-',
                        'chassis' => $ef->car_model_name,
                        'engine'  => $this->formatEngine($ef),
                        'results' => $resultMap,
                    ];
                }

                $seasons[$seasonFirst->season_year] = [
                    'season_id' => (int) $seasonId,
                    'year'      => $seasonFirst->season_year,
                    'calendar'  => $calendar,
                    'entries'   => $entries,
                ];
            }

            ksort($seasons);

            $grid[$seriesName] = [
                'series_name'   => $seriesName,
                'is_multiclass' => $isMulticlass,
                'is_spec'       => $isSpec,
                'seasons'       => $seasons,
            ];
        }

        return $grid;
    }

    /**
     * Format a result row into a short display string ("P3", "DNF", etc.).
     * Uses class_position for multiclass series when available.
     */
    private function formatResult(object $result): string
    {
        if ($result->status === 'finished') {
            $pos = $result->class_position ?: $result->position;
            return $pos ? (string) $pos : '-';
        }

        return strtoupper($result->status ?? '-');
    }

    /**
     * Build "ModelName Configuration Capacity" engine string.
     */
    private function formatEngine(object $row): string
    {
        $parts = array_filter([
            $row->engine_name,
            $row->engine_configuration,
            $row->engine_capacity ? $row->engine_capacity . 'cc' : null,
        ]);

        return implode(' ', $parts);
    }
}
