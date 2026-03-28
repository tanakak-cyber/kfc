<?php

use App\Models\GameMatch;
use App\Services\MatchResultSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * weight_g 移行後、順位表の集計を釣果から再計算して g 単位に揃える。
     */
    public function up(): void
    {
        if (! Schema::hasColumn('catches', 'weight_g')) {
            return;
        }

        $sync = app(MatchResultSyncService::class);

        foreach (GameMatch::query()->orderBy('id')->cursor() as $match) {
            $sync->syncMatch($match, true);
        }
    }

    public function down(): void
    {
        // 再計算前の値は保持しない
    }
};
