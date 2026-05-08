<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Result;
use App\Models\LapRecord;
use App\Models\LapRecordLog;
use App\Services\LapRecordService;

class PopulateLapRecords extends Command
{
    protected $signature = 'lap:populate-records
                            {--world= : Restrict to a specific world ID}
                            {--fresh  : Clear existing records and logs before repopulating}';

    protected $description = 'Seed lap_records and lap_record_logs from all existing results';

    public function handle(LapRecordService $service): int
    {
        $worldId = $this->option('world') ? (int) $this->option('world') : null;

        if ($this->option('fresh')) {
            $this->clearExisting($worldId);
        }

        $query = Result::whereNotNull('fastest_lap_time_ms')
            ->where('fastest_lap_time_ms', '>', 0)
            ->join('race_sessions as rs', 'results.race_session_id', '=', 'rs.id')
            ->join('calendar_races as cr', 'rs.calendar_race_id', '=', 'cr.id')
            ->join('seasons as s', 'cr.season_id', '=', 's.id')
            ->orderBy('cr.race_date', 'asc')
            ->select('results.*');

        if ($worldId) {
            $query->where('s.world_id', $worldId);
        }

        $updated = 0;
        $total   = 0;

        $query->with([
            'raceSession.calendarRace.season',
            'resultDrivers',
        ])->chunk(200, function ($results) use ($service, &$updated, &$total) {
            foreach ($results as $result) {
                $total++;
                if ($service->checkAndUpdate($result)) {
                    $updated++;
                }
            }
        });

        $this->info("Processed {$total} result(s). {$updated} new/updated lap record(s) set.");
        return 0;
    }

    /**
     * Wipe existing records (and optionally scoped to a world) before rebuilding.
     */
    private function clearExisting(?int $worldId): void
    {
        $this->warn('Clearing existing lap records and logs...');

        $query = fn($model) => $worldId
            ? $model::where('world_id', $worldId)
            : $model::query();

        $query(LapRecordLog::class)->delete();
        $query(LapRecord::class)->delete();

        $this->info('Cleared.');
    }
}
