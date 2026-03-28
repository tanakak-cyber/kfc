<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('held_at');
            $table->string('field');
            $table->string('launch_shop')->nullable();
            $table->text('rules')->nullable();
            $table->string('status', 32)->default('scheduled');
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();

            $table->index(['season_id', 'held_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
