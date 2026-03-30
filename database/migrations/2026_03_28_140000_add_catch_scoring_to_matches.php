<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('catch_scoring_basis', 20)->default('weight');
            $table->unsignedTinyInteger('catch_scoring_limit')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['catch_scoring_basis', 'catch_scoring_limit']);
        });
    }
};
