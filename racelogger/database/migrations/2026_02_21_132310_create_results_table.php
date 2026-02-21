<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('calendar_race_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entry_car_id')
                ->constrained()
                ->restrictOnDelete();

            $table->integer('position')->nullable();
            $table->enum('status', ['finished', 'dnf', 'dsq', 'dns']);

            $table->bigInteger('gap_to_leader_ms')->nullable();
            $table->integer('laps_completed')->default(0);

            $table->bigInteger('fastest_lap_time_ms')->nullable();
            $table->boolean('fastest_lap')->default(false);

            $table->decimal('points_awarded', 8, 2)->default(0);

            $table->timestamps();

            $table->unique(['calendar_race_id', 'entry_car_id']);
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
