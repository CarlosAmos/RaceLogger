<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('qualifying_sessions', 'race_number')) {
            Schema::table('qualifying_sessions', function (Blueprint $table) {
                $table->unsignedTinyInteger('race_number')->default(1)->after('calendar_race_id');
            });
        }

        $indexes = DB::select("SHOW INDEX FROM qualifying_sessions");
        $indexNames = array_column($indexes, 'Key_name');

        $newUnique = 'qual_sessions_race_cal_race_num_order_unique';
        $oldUnique = 'qualifying_sessions_calendar_race_id_session_order_unique';

        if (!in_array($newUnique, $indexNames)) {
            Schema::table('qualifying_sessions', function (Blueprint $table) use ($newUnique) {
                $table->unique(['calendar_race_id', 'race_number', 'session_order'], $newUnique);
            });
        }

        if (in_array($oldUnique, $indexNames)) {
            Schema::table('qualifying_sessions', function (Blueprint $table) use ($oldUnique) {
                $table->dropUnique($oldUnique);
            });
        }
    }

    public function down(): void
    {
        Schema::table('qualifying_sessions', function (Blueprint $table) {
            $table->unique(['calendar_race_id', 'session_order']);
        });

        Schema::table('qualifying_sessions', function (Blueprint $table) {
            $table->dropUnique('qual_sessions_race_cal_race_num_order_unique');
            $table->dropColumn('race_number');
        });
    }
};
