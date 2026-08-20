<?php

namespace App\Providers;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettingsService::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        } elseif (! $this->app->runningInConsole()) {
            $request = $this->app['request'];
            $host = $request->getHost();

            if ($host !== '') {
                URL::forceRootUrl($request->getSchemeAndHttpHost());
                URL::forceScheme($request->getScheme());
            }
        }

        View::composer('admin.*', function ($view) {
            try {
                /** @var SiteSettingsService $settings */
                $settings = app(SiteSettingsService::class);
                $view->with([
                    'adminSiteName' => $settings->get('site_name', 'TubeVault'),
                    'adminSiteLogo' => $settings->logoUrl(),
                    'adminSiteFavicon' => $settings->faviconUrl(),
                ]);
            } catch (\Throwable) {
                $view->with([
                    'adminSiteName' => 'TubeVault',
                    'adminSiteLogo' => null,
                    'adminSiteFavicon' => '/favicon.svg',
                ]);
            }
        });
    }
}
