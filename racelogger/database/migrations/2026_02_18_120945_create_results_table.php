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
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            // 🔗 What race weekend this result belongs to
            // $table->foreignId('calendar_race_id')
            //     ->constrained()
            //     ->cascadeOnDelete();

            // 🔗 Car (recommended instead of driver directly)
            $table->foreignId('car_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional: Driver (for single-driver series like F1)
            $table->foreignId('driver_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Classification
            $table->integer('position')->nullable();
            $table->integer('class_position')->nullable();

            // Timing data
            $table->string('time')->nullable();         // e.g. +5.432 or total race time
            $table->integer('laps_completed')->nullable();
            $table->string('status')->nullable();       // Finished, DNF, DNS, DSQ

            // Points (can override auto calculation later)
            $table->decimal('points', 6, 2)->nullable();

            $table->timestamps();

            // Prevent duplicate car entries per race
            $table->unique(['calendar_race_id', 'car_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
