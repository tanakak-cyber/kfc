<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // true（既定）: 撮影日時が無い画像は投稿不可（従来動作）
            // false: 撮影日時が無い画像も受け付ける
            $table->boolean('require_capture_datetime')->default(true)->after('catch_scoring_limit');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('require_capture_datetime');
        });
    }
};
