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
        Schema::create('car_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_team_entry_id')->constrained()->cascadeOnDelete();
            $table->string('car_model_name');
            $table->string('number', 10);
            $table->timestamps();

            $table->index('season_team_entry_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_entries');
    }
};
