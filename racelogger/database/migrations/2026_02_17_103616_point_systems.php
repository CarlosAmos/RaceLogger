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
        Schema::create('points_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            $table->boolean('fastest_lap_enabled')->default(false);
            $table->integer('fastest_lap_points')->nullable();
            $table->integer('fastest_lap_min_position')->nullable();

            $table->boolean('pole_position_enabled')->default(false);
            $table->integer('pole_position_points')->nullable();

            $table->boolean('quali_bonus_enabled')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_systems');
    }
};
