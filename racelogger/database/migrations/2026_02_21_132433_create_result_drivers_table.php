<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_drivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('driver_id')
                ->constrained()
                ->restrictOnDelete();

            $table->integer('driver_order')->default(1);

            $table->timestamps();

            $table->unique(['result_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_drivers');
    }
};