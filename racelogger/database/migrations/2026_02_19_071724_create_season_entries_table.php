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
        Schema::create('season_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('season_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('series_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['entrant_id', 'season_id', 'series_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_entries');
    }
};
