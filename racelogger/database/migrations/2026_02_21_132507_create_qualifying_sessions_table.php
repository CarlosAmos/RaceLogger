<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualifying_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('calendar_race_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->integer('session_order');

            $table->timestamps();

            $table->unique(['calendar_race_id', 'session_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualifying_sessions');
    }
};