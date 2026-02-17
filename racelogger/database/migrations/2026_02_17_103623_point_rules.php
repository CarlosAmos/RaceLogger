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
        Schema::create('points_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('points_system_id')->constrained()->cascadeOnDelete();
            $table->string('session_type');
            $table->integer('position');
            $table->decimal('points', 8, 2);
            $table->timestamps();

            $table->unique(['points_system_id', 'session_type', 'position']);
            $table->index(['points_system_id', 'session_type']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_rules');
    }
};
