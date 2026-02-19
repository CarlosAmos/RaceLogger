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
        //
        Schema::create('entry_cars', function (Blueprint $table) {
            $table->id();

            $table->foreignId('season_entry_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('entry_class_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('car_model_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('car_number'); // e.g. 7, 16, 01
            $table->string('livery_name')->nullable();
            $table->string('chassis_code')->nullable(); // optional realism

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
