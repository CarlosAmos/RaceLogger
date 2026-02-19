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
        Schema::dropIfExists('entry_classes');

        Schema::create('entry_classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('season_entry_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('race_class_id')
                ->constrained('season_classes')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['season_entry_id', 'race_class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_classes', function (Blueprint $table) {
            $table->dropForeign(['race_class_id']);
        });
    }
};
