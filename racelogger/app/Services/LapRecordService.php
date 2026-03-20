<?php

namespace App\Services;

use App\Models\Result;
use App\Models\LapRecord;
use App\Models\LapRecordLog;

class LapRecordService
{
    /**
     * Check whether the given result contains a fastest lap that beats the current
     * track layout record. If so, update lap_records and append to lap_record_logs.
     *
     * Returns true if a new record was set, false otherwise.
     */
    public function checkAndUpdate(Result $result): bool
    {
        if (!$result->fastest_lap_time_ms || $result->fastest_lap_time_ms <= 0) {
            return false;
        }

        $result->loadMissing([
            'raceSession.calendarRace.season.series',
            'resultDrivers',
        ]);

        $session = $result->raceSession;
        $calRace = $session->calendarRace;
        $series  = $calRace->season->series;

        $worldId       = $series->world_id;
        $trackLayoutId = $calRace->track_layout_id;
        $sessionType   = $session->is_sprint ? 'sprint' : 'race';
        $newTimeMs     = $result->fastest_lap_time_ms;

        $existing = LapRecord::where('world_id', $worldId)
            ->where('track_layout_id', $trackLayoutId)
            ->where('session_type', $sessionType)
            ->first();

        if ($existing && $existing->lap_time_ms <= $newTimeMs) {
            return false;
        }

        $driverId = $result->resultDrivers->sortBy('driver_order')->first()?->driver_id;

        $payload = [
            'world_id'        => $worldId,
            'track_layout_id' => $trackLayoutId,
            'session_type'    => $sessionType,
            'driver_id'       => $driverId,
            'season_id'       => $calRace->season_id,
            'lap_time_ms'     => $newTimeMs,
            'record_date'     => $calRace->race_date,
        ];

        LapRecordLog::create($payload);

        LapRecord::updateOrCreate(
            [
                'world_id'        => $worldId,
                'track_layout_id' => $trackLayoutId,
                'session_type'    => $sessionType,
            ],
            array_diff_key($payload, array_flip(['world_id', 'track_layout_id', 'session_type']))
        );

        return true;
    }
}
