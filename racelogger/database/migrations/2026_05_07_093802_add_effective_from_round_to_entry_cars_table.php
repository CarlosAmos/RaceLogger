<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entry_cars', function (Blueprint $table) {
            if (!Schema::hasColumn('entry_cars', 'effective_from_round')) {
                $table->unsignedSmallInteger('effective_from_round')->default(1)->after('chassis_code');
            }
        });

        // Deduplicate rows: merge child records into the kept row, then delete duplicates.
        $duplicates = DB::select("
            SELECT id, entry_class_id, car_number, effective_from_round
            FROM entry_cars
            WHERE (entry_class_id, car_number, effective_from_round) IN (
                SELECT entry_class_id, car_number, effective_from_round
                FROM entry_cars
                GROUP BY entry_class_id, car_number, effective_from_round
                HAVING COUNT(*) > 1
            )
            ORDER BY entry_class_id, car_number, effective_from_round, id
        ");

        $groups = [];
        foreach ($duplicates as $row) {
            $key = "{$row->entry_class_id}_{$row->car_number}_{$row->effective_from_round}";
            $groups[$key][] = $row->id;
        }

        foreach ($groups as $ids) {
            $keepId = min($ids);
            $deleteIds = array_filter($ids, fn($id) => $id !== $keepId);

            foreach ($deleteIds as $deleteId) {
                // For qualifying_results: drop conflicts (keep's session wins), then reassign the rest
                $keepQualSessionIds = DB::table('qualifying_results')
                    ->where('entry_car_id', $keepId)
                    ->pluck('qualifying_session_id');
                DB::table('qualifying_results')
                    ->where('entry_car_id', $deleteId)
                    ->whereIn('qualifying_session_id', $keepQualSessionIds)
                    ->delete();
                DB::table('qualifying_results')
                    ->where('entry_car_id', $deleteId)
                    ->update(['entry_car_id' => $keepId]);

                // For results: drop conflicts (keep's session wins), then reassign the rest
                $keepRaceSessionIds = DB::table('results')
                    ->where('entry_car_id', $keepId)
                    ->pluck('race_session_id');
                DB::table('results')
                    ->where('entry_car_id', $deleteId)
                    ->whereIn('race_session_id', $keepRaceSessionIds)
                    ->delete();
                DB::table('results')
                    ->where('entry_car_id', $deleteId)
                    ->update(['entry_car_id' => $keepId]);

                // For drivers: skip already-assigned, then reassign the rest
                $keepDriverIds = DB::table('entry_car_driver')
                    ->where('entry_car_id', $keepId)
                    ->pluck('driver_id');
                DB::table('entry_car_driver')
                    ->where('entry_car_id', $deleteId)
                    ->whereIn('driver_id', $keepDriverIds)
                    ->delete();
                DB::table('entry_car_driver')
                    ->where('entry_car_id', $deleteId)
                    ->update(['entry_car_id' => $keepId]);

                DB::table('entry_cars')->where('id', $deleteId)->delete();
            }
        }

        Schema::table('entry_cars', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('entry_cars'))->pluck('name');

            if ($indexes->contains('entry_cars_entry_class_id_car_number_unique')) {
                $table->dropUnique(['entry_class_id', 'car_number']);
            }

            if (!$indexes->contains('entry_cars_entry_class_id_car_number_effective_from_round_unique')) {
                $table->unique(['entry_class_id', 'car_number', 'effective_from_round']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('entry_cars', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('entry_cars'))->pluck('name');

            if ($indexes->contains('entry_cars_entry_class_id_car_number_effective_from_round_unique')) {
                $table->dropUnique(['entry_class_id', 'car_number', 'effective_from_round']);
            }

            if (!$indexes->contains('entry_cars_entry_class_id_car_number_unique')) {
                $table->unique(['entry_class_id', 'car_number']);
            }

            if (Schema::hasColumn('entry_cars', 'effective_from_round')) {
                $table->dropColumn('effective_from_round');
            }
        });
    }
};
