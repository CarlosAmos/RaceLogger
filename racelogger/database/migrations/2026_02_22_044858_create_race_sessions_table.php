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
            $table->string('name'); // Race 1, Sprint, Feature Race, etc
            $table->integer('session_order');
            $table->boolean('is_sprint')->default(false);
            $table->boolean('reverse_grid')->default(false);
            $table->integer('reverse_grid_from_position')->nullable();
            $table->timestamps();
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
