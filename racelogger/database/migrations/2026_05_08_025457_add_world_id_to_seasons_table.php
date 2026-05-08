<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedBigInteger('world_id')->nullable()->after('id');
            $table->foreign('world_id')->references('id')->on('worlds')->cascadeOnDelete();
            $table->index('world_id');
        });

        DB::statement('UPDATE seasons s JOIN series ser ON s.series_id = ser.id SET s.world_id = ser.world_id');

        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedBigInteger('world_id')->nullable(false)->change();
            $table->unique(['world_id', 'series_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropUnique(['world_id', 'series_id', 'year']);
            $table->dropForeign(['world_id']);
            $table->dropIndex(['world_id']);
            $table->dropColumn('world_id');
        });
    }
};
