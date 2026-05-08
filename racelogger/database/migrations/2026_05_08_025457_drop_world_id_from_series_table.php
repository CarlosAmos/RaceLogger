<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropForeign(['world_id']);
            $table->dropColumn('world_id');
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->unsignedBigInteger('world_id')->nullable()->after('id');
            $table->foreign('world_id')->references('id')->on('worlds')->cascadeOnDelete();
        });
    }
};
