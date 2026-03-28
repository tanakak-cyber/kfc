<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        Schema::table('match_results', function (Blueprint $table) {
            $table->dropUnique(['match_id', 'team_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE match_results MODIFY team_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('match_results', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->change();
            });
        }

        Schema::table('match_results', function (Blueprint $table) {
            $table->foreignId('player_id')->nullable()->after('team_id')->constrained('players')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->unique(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->dropUnique(['match_id', 'player_id']);
            $table->dropForeign(['player_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('player_id');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE match_results MODIFY team_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('match_results', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable(false)->change();
            });
        }

        Schema::table('match_results', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->unique(['match_id', 'team_id']);
        });
    }
};
