<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->boolean('is_present')->default(true);
            $table->string('entry_token', 32)->unique();
            $table->timestamps();

            $table->unique(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};
