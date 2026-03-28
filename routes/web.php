<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\CatchModerationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameMatchManageController;
use App\Http\Controllers\Admin\MatchFishCatchController;
use App\Http\Controllers\Admin\MatchTeamManageController;
use App\Http\Controllers\Admin\PlayerManageController;
use App\Http\Controllers\Admin\SeasonManageController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\UserManageController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\PublicMatchController;
use App\Http\Controllers\SeasonController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/seasons', [SeasonController::class, 'index'])->name('seasons.index');
Route::get('/seasons/{season}', [SeasonController::class, 'show'])->name('seasons.show');

Route::get('/matches/{gameMatch}', [PublicMatchController::class, 'show'])->name('matches.show');

Route::get('/entry/{token}', [EntryController::class, 'show'])->name('entry.show');
Route::post('/entry/{token}', [EntryController::class, 'store'])->name('entry.store');

Route::get('/players/{player}', [PlayerProfileController::class, 'show'])->name('players.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('site', [SiteSettingsController::class, 'edit'])->name('site.edit');
    Route::put('site', [SiteSettingsController::class, 'update'])->name('site.update');

    Route::resource('seasons', SeasonManageController::class)->except(['show']);
    Route::resource('players', PlayerManageController::class)->except(['show']);

    Route::resource('matches', GameMatchManageController::class)
        ->parameters(['matches' => 'gameMatch'])
        ->except(['show', 'destroy']);

    Route::post('matches/{gameMatch}/finalize', [GameMatchManageController::class, 'finalize'])
        ->name('matches.finalize');

    Route::post('matches/{gameMatch}/unfinalize', [GameMatchManageController::class, 'unfinalize'])
        ->name('matches.unfinalize');

    Route::post('matches/{gameMatch}/recalculate-season-player-points', [GameMatchManageController::class, 'recalculateSeasonPlayerPoints'])
        ->name('matches.recalculate-season-player-points');

    Route::post('matches/{gameMatch}/resync-match-results-and-season', [GameMatchManageController::class, 'resyncMatchResultsAndSeason'])
        ->name('matches.resync-match-results-and-season');

    Route::get('matches/{gameMatch}/catches/{fishCatch}/edit', [MatchFishCatchController::class, 'edit'])
        ->name('matches.catches.edit');
    Route::put('matches/{gameMatch}/catches/{fishCatch}', [MatchFishCatchController::class, 'update'])
        ->name('matches.catches.update');

    Route::get('matches/{gameMatch}/teams', [MatchTeamManageController::class, 'index'])
        ->name('matches.teams.index');
    Route::post('matches/{gameMatch}/teams', [MatchTeamManageController::class, 'store'])
        ->name('matches.teams.store');
    Route::delete('matches/{gameMatch}/teams/{team}', [MatchTeamManageController::class, 'destroy'])
        ->name('matches.teams.destroy');

    Route::resource('users', UserManageController::class)->except(['show']);

    Route::get('catches/pending', [CatchModerationController::class, 'index'])->name('catches.pending');
    Route::post('catches/{fishCatch}/approve', [CatchModerationController::class, 'approve'])->name('catches.approve');
    Route::post('catches/{fishCatch}/reject', [CatchModerationController::class, 'reject'])->name('catches.reject');
});
