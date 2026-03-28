<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->unsignedSmallInteger('weight_g')->default(0)->after('length_cm');
        });

        foreach (DB::table('catches')->orderBy('id')->cursor() as $row) {
            $g = (int) round((float) $row->weight_kg * 1000);
            $g = max(0, min(65535, $g));
            DB::table('catches')->where('id', $row->id)->update(['weight_g' => $g]);
        }

        Schema::table('catches', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->default(0)->after('length_cm');
        });

        foreach (DB::table('catches')->orderBy('id')->cursor() as $row) {
            $kg = round(((int) $row->weight_g) / 1000, 3);
            DB::table('catches')->where('id', $row->id)->update(['weight_kg' => $kg]);
        }

        Schema::table('catches', function (Blueprint $table) {
            $table->dropColumn('weight_g');
        });
    }
};
