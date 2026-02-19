<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('season_entries', function (Blueprint $table) {
            $table->foreignId('constructor_id')
                ->after('series_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('season_entries', function (Blueprint $table) {
            $table->dropForeign(['constructor_id']);
            $table->dropColumn('constructor_id');
        });
    }
};
