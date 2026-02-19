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
        Schema::create('entrants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('world_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('constructor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('country_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name'); // AF Corse, Porsche Penske Motorsport

            $table->timestamps();

            $table->unique(['world_id', 'name']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrants');
    }
};
