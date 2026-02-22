<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {

            // Remove old column if still exists
            if (Schema::hasColumn('results', 'calendar_race_id')) {
                $table->dropForeign(['calendar_race_id']);
                $table->dropColumn('calendar_race_id');
            }

            if (Schema::hasColumn('results', 'session_type')) {
                $table->dropColumn('session_type');
            }

            // Add class_position if missing
            if (!Schema::hasColumn('results', 'class_position')) {
                $table->integer('class_position')->nullable()->after('position');
            }
        });
    }

    public function down(): void
    {
        // Optional rollback logic
    }
};
