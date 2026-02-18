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
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('race_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('car_entry_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('position')->nullable();
            $table->decimal('points_awarded', 8, 2)->default(0);

            $table->timestamps();

            $table->unique(['race_session_id', 'car_entry_id']);

            // ✅ INDEXES
            $table->index('car_entry_id');
            $table->index('race_session_id');
            $table->index('position');

            $table->foreignId('calendar_race_id')
            ->constrained()
            ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
