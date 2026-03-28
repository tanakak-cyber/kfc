<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('match_surveys')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('selected_field_id')->constrained('match_survey_fields')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['survey_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_survey_answers');
    }
};
