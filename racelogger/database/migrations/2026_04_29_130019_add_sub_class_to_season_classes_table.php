<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add new unique index first so season_id always has an index (required by MySQL for the FK)
        Schema::table('season_classes', function (Blueprint $table) {
            if (!DB::select("SHOW COLUMNS FROM season_classes LIKE 'sub_class'")) {
                $table->string('sub_class')->nullable()->after('name');
            }
            $table->unique(['season_id', 'name', 'sub_class'], 'season_classes_season_id_name_sub_class_unique');
        });

        Schema::table('season_classes', function (Blueprint $table) {
            $table->dropUnique('season_classes_season_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('season_classes', function (Blueprint $table) {
            $table->unique(['season_id', 'name'], 'season_classes_season_id_name_unique');
        });

        Schema::table('season_classes', function (Blueprint $table) {
            $table->dropUnique('season_classes_season_id_name_sub_class_unique');
            $table->dropColumn('sub_class');
        });
    }
};
