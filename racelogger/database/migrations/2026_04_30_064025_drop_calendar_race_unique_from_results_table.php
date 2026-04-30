<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Drop the stale composite unique index — it was meant for the old
            // schema where one result existed per (calendar_race, entry_car).
            // Now results are scoped to race_session_id, so multiple sessions
            // (e.g. Sprint + Main Race) can exist for the same calendar race.
            $indexes = DB::select("SHOW INDEX FROM results WHERE Key_name = 'results_calendar_race_id_entry_car_id_unique'");
            if (!empty($indexes)) {
                $table->dropUnique('results_calendar_race_id_entry_car_id_unique');
            }

            // Drop the column if a prior migration failed to remove it
            if (Schema::hasColumn('results', 'calendar_race_id')) {
                $table->dropColumn('calendar_race_id');
            }
        });
    }

    public function down(): void
    {
        // Cannot safely recreate — the constraint is no longer valid
    }
};
