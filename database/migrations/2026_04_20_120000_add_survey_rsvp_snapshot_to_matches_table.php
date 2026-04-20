<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('source_match_survey_id')
                ->nullable()
                ->after('season_id')
                ->constrained('match_surveys')
                ->nullOnDelete();
            $table->json('survey_rsvp_snapshot')->nullable()->after('source_match_survey_id');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['source_match_survey_id']);
            $table->dropColumn(['source_match_survey_id', 'survey_rsvp_snapshot']);
        });
    }
};
