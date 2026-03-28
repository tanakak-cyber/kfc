<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_survey_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('match_surveys')->cascadeOnDelete();
            $table->string('field_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_survey_fields');
    }
};
