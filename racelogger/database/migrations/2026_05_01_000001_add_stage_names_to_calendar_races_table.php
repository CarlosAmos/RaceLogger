<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_races', function (Blueprint $table) {
            $table->json('stage_names')->nullable()->after('special_event');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_races', function (Blueprint $table) {
            $table->dropColumn('stage_names');
        });
    }
};
