<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatchApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\Season;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $seasonsCount = Season::query()->count();
        $matchesCount = GameMatch::query()->count();
        $pendingCatches = FishCatch::query()
            ->where('approval_status', CatchApprovalStatus::Pending)
            ->count();

        return view('admin.dashboard', compact('seasonsCount', 'matchesCount', 'pendingCatches'));
    }
}
