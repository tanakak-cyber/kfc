<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('rank')->nullable();
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->decimal('big_fish_weight', 10, 3)->default(0);
            $table->unsignedTinyInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['match_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
