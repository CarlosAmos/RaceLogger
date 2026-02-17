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
        Schema::create('calendar_races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_layout_id')->constrained()->restrictOnDelete();

            $table->integer('round_number');
            $table->string('name');
            $table->date('race_date');
            $table->timestamps();

            $table->unique(['season_id', 'round_number']);

            $table->index('season_id');
            $table->index(['season_id', 'round_number']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_races');
    }
};
