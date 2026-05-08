<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Merge duplicate drivers (same first_name + last_name across worlds) into a
     * single canonical row, then repoint all FK references to that row.
     *
     * Canonical selection priority:
     *   1. Row with country_id set (lowest id wins if multiple qualify)
     *   2. Lowest id overall (if none have a country)
     */
    public function up(): void
    {
        // All name groups that have more than one driver row
        $duplicateGroups = DB::table('drivers')
            ->select('first_name', 'last_name')
            ->groupBy('first_name', 'last_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $drivers = DB::table('drivers')
                ->where('first_name', $group->first_name)
                ->where('last_name',  $group->last_name)
                ->orderByRaw('country_id IS NULL ASC') // rows with country first
                ->orderBy('id')
                ->get();

            $canonical  = $drivers->first();
            $duplicates = $drivers->skip(1)->values();

            foreach ($duplicates as $dupe) {
                // ── entry_car_driver ──────────────────────────────────────────
                $canonicalCarIds = DB::table('entry_car_driver')
                    ->where('driver_id', $canonical->id)
                    ->pluck('entry_car_id')
                    ->flip()
                    ->all();

                $dupePivots = DB::table('entry_car_driver')
                    ->where('driver_id', $dupe->id)
                    ->get();

                foreach ($dupePivots as $pivot) {
                    if (isset($canonicalCarIds[$pivot->entry_car_id])) {
                        // Canonical already on this car — drop the duplicate row
                        DB::table('entry_car_driver')
                            ->where('driver_id', $dupe->id)
                            ->where('entry_car_id', $pivot->entry_car_id)
                            ->delete();
                    } else {
                        DB::table('entry_car_driver')
                            ->where('driver_id', $dupe->id)
                            ->where('entry_car_id', $pivot->entry_car_id)
                            ->update(['driver_id' => $canonical->id]);
                    }
                }

                // ── result_drivers ────────────────────────────────────────────
                // result_id is world-scoped so true duplicates won't occur,
                // but guard with the same pattern just in case
                $canonicalResultIds = DB::table('result_drivers')
                    ->where('driver_id', $canonical->id)
                    ->pluck('result_id')
                    ->flip()
                    ->all();

                $dupeResults = DB::table('result_drivers')
                    ->where('driver_id', $dupe->id)
                    ->get();

                foreach ($dupeResults as $row) {
                    if (isset($canonicalResultIds[$row->result_id])) {
                        DB::table('result_drivers')
                            ->where('driver_id', $dupe->id)
                            ->where('result_id', $row->result_id)
                            ->delete();
                    } else {
                        DB::table('result_drivers')
                            ->where('driver_id', $dupe->id)
                            ->where('result_id', $row->result_id)
                            ->update(['driver_id' => $canonical->id]);
                    }
                }

                // ── lap_records — nullable driver_id, straightforward update ──
                DB::table('lap_records')
                    ->where('driver_id', $dupe->id)
                    ->update(['driver_id' => $canonical->id]);

                // ── Delete the now-orphaned duplicate driver row ───────────────
                DB::table('drivers')->where('id', $dupe->id)->delete();
            }
        }
    }

    /**
     * No down() — deduplication is not reversible without a full backup.
     * The dropped duplicate rows cannot be reconstructed from data alone.
     */
    public function down(): void
    {
        // intentionally empty
    }
};
