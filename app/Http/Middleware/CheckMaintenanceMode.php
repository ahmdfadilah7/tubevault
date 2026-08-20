<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function __construct(private SiteSettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->bool('maintenance_mode')) {
            return $next($request);
        }

        if ($request->is('my-panel') || $request->is('my-panel/*') || $request->is('api/*') || $request->is('up') || $request->is('storage/*') || $request->is('robots.txt') || $request->is('sitemap.xml')) {
            return $next($request);
        }

        if ($request->user()?->is_admin) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'siteName' => $this->settings->get('site_name'),
            'message' => $this->settings->get('maintenance_message'),
            'logoUrl' => $this->settings->logoUrl(),
            'faviconUrl' => $this->settings->faviconUrl(),
            'themeColor' => $this->settings->get('theme_color', '#07080c'),
        ], 503);
    }
}
