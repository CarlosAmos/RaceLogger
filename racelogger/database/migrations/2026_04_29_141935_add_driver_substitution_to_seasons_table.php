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
        Schema::table('seasons', function (Blueprint $table) {
            $table->unsignedBigInteger('replace_driver_id')->nullable()->after('point_system_id');
            $table->unsignedBigInteger('substitute_driver_id')->nullable()->after('replace_driver_id');

            $table->foreign('replace_driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->foreign('substitute_driver_id')->references('id')->on('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropForeign(['replace_driver_id']);
            $table->dropForeign(['substitute_driver_id']);
            $table->dropColumn(['replace_driver_id', 'substitute_driver_id']);
        });
    }
};
