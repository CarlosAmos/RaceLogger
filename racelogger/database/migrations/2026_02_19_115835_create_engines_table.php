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
        Schema::create('engines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('world_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');                // Ferrari 066/7
            $table->string('configuration')->nullable(); // V6, V8, V10
            $table->string('capacity')->nullable();      // 1.6L, 3.5L
            $table->boolean('hybrid')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engines');
    }
};
