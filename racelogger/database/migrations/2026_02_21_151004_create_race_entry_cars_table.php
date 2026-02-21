<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_entry_cars', function (Blueprint $table) {
            $table->id();

            $table->foreignId('calendar_race_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entry_car_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['calendar_race_id', 'entry_car_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_entry_cars');
    }
};