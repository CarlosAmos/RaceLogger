<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualifying_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('qualifying_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entry_car_id')
                ->constrained()
                ->restrictOnDelete();

            $table->integer('position')->nullable();
            $table->bigInteger('best_lap_time_ms')->nullable();
            $table->bigInteger('average_lap_time_ms')->nullable();
            $table->integer('laps_set')->nullable();

            $table->timestamps();

            $table->unique(['qualifying_session_id', 'entry_car_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualifying_results');
    }
};