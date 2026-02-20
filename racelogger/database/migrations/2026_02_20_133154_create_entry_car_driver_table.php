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
        Schema::create('entry_car_driver', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entry_car_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('driver_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['entry_car_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_car_driver');
    }
};
