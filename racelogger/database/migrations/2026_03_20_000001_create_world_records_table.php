<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('data')->nullable();       // JSON blob of all computed records
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('world_records');
    }
};
