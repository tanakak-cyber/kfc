<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->renameColumn('held_at', 'start_datetime');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dateTime('end_datetime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('end_datetime');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->string('status', 32)->default('scheduled');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->renameColumn('start_datetime', 'held_at');
        });
    }
};
