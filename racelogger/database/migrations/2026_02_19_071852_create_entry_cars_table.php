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
        Schema::create('entry_cars', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entry_class_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('car_model_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('car_number'); // #50, #6, etc

            $table->timestamps();

            $table->unique(['entry_class_id', 'car_number']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_cars');
    }
};
