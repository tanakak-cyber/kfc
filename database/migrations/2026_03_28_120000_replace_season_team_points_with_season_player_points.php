<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('season_team_points');
        Schema::dropIfExists('season_player_points');

        Schema::create('season_player_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->timestamps();

            $table->unique(['season_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_player_points');

        Schema::create('season_team_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->timestamps();
            $table->unique(['season_id', 'team_id']);
        });
    }
};
