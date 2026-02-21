<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_system_bonus_rules', function (Blueprint $table) {
            $table->integer('min_position_required')->nullable()->after('points');
            $table->boolean('requires_finish')->default(true)->after('min_position_required');
        });
    }

    public function down(): void
    {
        Schema::table('point_system_bonus_rules', function (Blueprint $table) {
            $table->dropColumn(['min_position_required', 'requires_finish']);
        });
    }
};