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
        Schema::create('race_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_race_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // FP1, Qualifying, Race
            $table->string('type'); // practice, qualifying, race, sprint
            $table->integer('session_order');

            $table->timestamps();

            $table->index('calendar_race_id');
            $table->index(['calendar_race_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_sessions');
    }
};
