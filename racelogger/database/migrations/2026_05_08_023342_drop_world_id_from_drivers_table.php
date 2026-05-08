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
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign(['world_id']);
            $table->dropColumn('world_id');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->unsignedBigInteger('world_id')->after('id')->nullable();
            $table->foreign('world_id')->references('id')->on('worlds')->cascadeOnDelete();
            $table->index(['world_id', 'last_name']);
        });
    }
};
