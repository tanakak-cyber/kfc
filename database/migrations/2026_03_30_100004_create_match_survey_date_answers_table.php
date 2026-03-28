<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_survey_date_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained('match_survey_answers')->cascadeOnDelete();
            $table->foreignId('date_id')->constrained('match_survey_dates')->cascadeOnDelete();
            $table->string('status', 8);
            $table->timestamps();

            $table->unique(['answer_id', 'date_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_survey_date_answers');
    }
};
