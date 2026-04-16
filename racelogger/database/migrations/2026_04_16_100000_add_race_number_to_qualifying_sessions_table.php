<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualifying_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('race_number')->default(1)->after('calendar_race_id');
            $table->dropUnique(['calendar_race_id', 'session_order']);
            $table->unique(['calendar_race_id', 'race_number', 'session_order']);
        });
    }

    public function down(): void
    {
        Schema::table('qualifying_sessions', function (Blueprint $table) {
            $table->dropUnique(['calendar_race_id', 'race_number', 'session_order']);
            $table->dropColumn('race_number');
            $table->unique(['calendar_race_id', 'session_order']);
        });
    }
};
