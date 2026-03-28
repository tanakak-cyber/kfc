<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE catches MODIFY team_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('catches', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->change();
            });
        }

        Schema::table('catches', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE catches MODIFY team_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('catches', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable(false)->change();
            });
        }

        Schema::table('catches', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }
};
