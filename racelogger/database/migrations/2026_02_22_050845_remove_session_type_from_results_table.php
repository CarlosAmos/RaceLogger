<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn('session_type');
        });
    }

    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('session_type');
        });
    }
};
