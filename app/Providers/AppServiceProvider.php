<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Support\PublicStorageUrl;
use App\Support\SiteHomeTagline;
use App\Support\SiteNoindex;
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
            $siteTeamName = SiteTeamName::get();
            $view->with('siteTeamName', $siteTeamName);
            $view->with('siteHomeTagline', SiteHomeTagline::get());
            $view->with('siteNoindex', SiteNoindex::enabled());

            $branding = SiteSetting::query()->first();
            $view->with(
                'headerSiteTitle',
                filled($branding?->site_name) ? (string) $branding->site_name : $siteTeamName
            );
            $view->with(
                'siteLogoUrl',
                PublicStorageUrl::fromDiskPath($branding?->logo_path) ?? '/images/logo-default.svg'
            );
            $view->with(
                'siteHeroImageUrl',
                PublicStorageUrl::fromDiskPath($branding?->home_hero_image_path)
            );
        });
    }
}
