<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->decimal('length_cm', 8, 2);
            $table->decimal('weight_kg', 10, 3);
            $table->string('approval_status', 32)->default('pending');
            $table->timestamps();

            $table->index(['match_id', 'team_id', 'approval_status']);
            $table->index(['team_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catches');
    }
};
