<?php

namespace App\Providers;

use App\Support\SiteHomeTagline;
use App\Support\SiteTeamName;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function (\Illuminate\View\View $view): void {
            $view->with('siteTeamName', SiteTeamName::get());
            $view->with('siteHomeTagline', SiteHomeTagline::get());
        });
    }
}
