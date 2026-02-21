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
        Schema::create('point_system_bonus_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_system_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['fastest_lap']);
            $table->integer('points');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_system_bonus_rules');
    }
};
