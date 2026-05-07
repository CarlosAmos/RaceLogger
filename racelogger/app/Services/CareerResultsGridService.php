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
            ->where('rs.is_sprint', false)
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
                'rs.session_order',
                'rs.name as session_name',
                'r.position',
                'r.class_position',
                'r.status',
                'r.points_awarded',
                'ec.id as entry_car_id',
                \DB::raw('COALESCE(se.display_name, e.name) as entrant_name'),
                'cm.name as car_model_name',
                'eng.name as engine_name',
                'eng.capacity as engine_capacity',
                'eng.configuration as engine_configuration',
                'sc.id as season_class_id',
                'sc.name as class_name',
                'sc.sub_class',
                'sc.display_order',
                'r.sub_class_position',
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
            ->select(['season_id', 'round_number', 'race_code', 'gp_name', 'special_event'])
            ->orderBy('round_number')
            ->get()
            ->groupBy('season_id');

        // Race sessions that exist so far
        $sessions = DB::table('race_sessions as rs')
            ->join('calendar_races as cr', 'rs.calendar_race_id', '=', 'cr.id')
            ->whereIn('cr.season_id', $seasonIds)
            ->where('rs.is_sprint', false)
            ->select(['cr.season_id', 'cr.round_number', 'rs.id as session_id', 'rs.is_sprint', 'rs.session_order', 'rs.name as session_name'])
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
                        'season_id'     => $seasonId,
                        'round_number'  => $race->round_number,
                        'race_code'     => $race->race_code,
                        'gp_name'       => $race->gp_name,
                        'special_event' => (bool) $race->special_event,
                        'session_id'    => null,
                        'is_sprint'     => null,
                        'session_name'  => null,
                    ]);
                } else {
                    foreach ($roundSessions as $session) {
                        $merged->push((object) [
                            'season_id'     => $seasonId,
                            'round_number'  => $race->round_number,
                            'race_code'     => $race->race_code,
                            'gp_name'       => $race->gp_name,
                            'special_event' => (bool) $race->special_event,
                            'session_id'    => $session->session_id,
                            'is_sprint'     => $session->is_sprint,
                            'session_order' => $session->session_order,
                            'session_name'  => $session->session_name ?? null,
                        ]);
                    }
                }
            }
        }

        return $merged->groupBy('season_id');
    }

    /**
     * Build the grid structure: series → seasons → entries → results.
     * Seasons with sub-classes (display_order != 1) also populate sub_cups sections.
     */
    private function buildGrid(Collection $results, Collection $calendars): array
    {
        $grid = [];

        foreach ($results->groupBy('series_id') as $seriesResults) {
            $first        = $seriesResults->first();
            $seriesName   = $first->series_name;
            $isMulticlass = (bool) $first->is_multiclass;
            $isSpec       = ($first->series_short_name === 'F2');

            $seasons    = [];
            $subCupData = []; // sub_class label → ['label', 'seasons']

            foreach ($seriesResults->groupBy('season_id') as $seasonId => $seasonResults) {
                $seasonFirst = $seasonResults->first();

                // Build ordered calendar (same as before)
                $calendar = [];
                foreach ($calendars->get($seasonId, collect()) as $row) {
                    $round = $row->round_number;
                    if (!isset($calendar[$round])) {
                        $calendar[$round] = [
                            'race_code'     => $row->race_code,
                            'gp_name'       => $row->gp_name,
                            'special_event' => $row->special_event,
                            'sessions'      => [],
                        ];
                    }
                    if ($row->session_id !== null) {
                        $calendar[$round]['sessions'][] = [
                            'session_id'    => $row->session_id,
                            'is_sprint'     => (bool) $row->is_sprint,
                            'session_order' => $row->session_order,
                            'name'          => $row->session_name ?? null,
                        ];
                    }
                }
                ksort($calendar);

                $roundToIndex = array_flip(array_keys($calendar));

                $entries             = [];
                $subCupEntriesBySeason = []; // sub_class → entries[]
                $subCupClassIds        = []; // sub_class → season_class_id

                foreach ($seasonResults->groupBy('entry_class_id') as $entryResults) {
                    $ef           = $entryResults->first();
                    $displayOrder = $ef->display_order;
                    $subClass     = $ef->sub_class;
                    $isSubCup     = ($displayOrder !== null && (int) $displayOrder !== 1 && $subClass !== null);

                    // Main result map uses class_position
                    $resultMap = [];
                    foreach ($entryResults as $res) {
                        $calIdx = $roundToIndex[$res->round_number] ?? null;
                        if ($calIdx !== null) {
                            $resultMap[$calIdx][$res->session_id] = $this->formatResult($res, false);
                        }
                    }

                    // Sub-class result map (only when this entry has a sub_class)
                    $subclassResultMap = null;
                    if ($ef->sub_class !== null) {
                        $subclassResultMap = [];
                        foreach ($entryResults as $res) {
                            $calIdx = $roundToIndex[$res->round_number] ?? null;
                            if ($calIdx !== null) {
                                $subclassResultMap[$calIdx][$res->session_id] = $this->formatResult($res, true);
                            }
                        }
                    }

                    $entryArr = [
                        'entrant'          => $ef->entrant_name,
                        'class'            => $ef->class_name ?? '-',
                        'chassis'          => $ef->car_model_name,
                        'engine'           => $this->formatEngine($ef),
                        'results'          => $resultMap,
                        'subclass_results' => $subclassResultMap,
                    ];

                    $entries[] = $entryArr;

                    if ($isSubCup) {
                        // results = class_position (display), subclass_results = sub_class_position (colour + small label)
                        $subCupEntriesBySeason[$subClass][] = $entryArr;
                        $subCupClassIds[$subClass]           = $ef->season_class_id;

                        if (!isset($subCupData[$subClass])) {
                            $subCupData[$subClass] = [
                                'label'   => $seriesName . ' ' . $subClass . ' Cup',
                                'seasons' => [],
                            ];
                        }
                    }
                }

                $seasons[$seasonFirst->season_year] = [
                    'season_id' => (int) $seasonId,
                    'year'      => $seasonFirst->season_year,
                    'calendar'  => array_values($calendar),
                    'entries'   => $entries,
                ];

                foreach ($subCupEntriesBySeason as $subClass => $subEntries) {
                    $subCupData[$subClass]['seasons'][$seasonFirst->season_year] = [
                        'season_id' => (int) $seasonId,
                        'class_id'  => $subCupClassIds[$subClass],
                        'calendar'  => array_values($calendar),
                        'entries'   => $subEntries,
                    ];
                }
            }

            ksort($seasons);

            $subCups = [];
            foreach ($subCupData as $data) {
                ksort($data['seasons']);
                $subCups[] = $data;
            }

            $grid[$seriesName] = [
                'series_name'   => $seriesName,
                'is_multiclass' => $isMulticlass,
                'is_spec'       => $isSpec,
                'seasons'       => $seasons,
                'sub_cups'      => $subCups,
            ];
        }

        return $grid;
    }

    /**
     * Format a result row into a short display string ("P3", "DNF", etc.).
     * Uses sub_class_position when $useSubClass is true, otherwise class_position.
     */
    private function formatResult(object $result, bool $useSubClass = false): string
    {
        if ($result->status === 'finished') {
            $pos = $useSubClass
                ? ($result->sub_class_position ?: $result->class_position ?: $result->position)
                : ($result->class_position ?: $result->position);
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
