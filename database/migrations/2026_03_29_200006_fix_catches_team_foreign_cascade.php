<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        Schema::table('catches', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        Schema::table('catches', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }
};
