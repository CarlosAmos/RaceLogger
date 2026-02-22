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
        Schema::table('results', function (Blueprint $table) {

            // Drop wrong foreign key
            $table->dropForeign(['entry_car_id']);

            // Re-add correct foreign key
            $table->foreign('entry_car_id')
                ->references('id')
                ->on('entry_cars')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('results', function (Blueprint $table) {

            $table->dropForeign(['entry_car_id']);

            $table->foreign('entry_car_id')
                ->references('id')
                ->on('race_sessions'); // old broken state
        });
    }
};
