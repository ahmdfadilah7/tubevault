<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    public function __invoke(SiteSettingsService $settings): JsonResponse
    {
        return response()->json([
            'site_name' => $settings->get('site_name'),
            'tagline' => $settings->get('tagline'),
            'description' => $settings->get('description'),
            'theme_color' => $settings->get('theme_color'),
            'footer_text' => $settings->get('footer_text'),
            'logo_url' => $settings->logoUrl(),
            'favicon_url' => $settings->faviconUrl(),
            'allow_registration' => $settings->bool('allow_registration'),
            'maintenance_mode' => $settings->bool('maintenance_mode'),
            'social' => [
                'tiktok' => $settings->get('social_tiktok'),
                'saweria' => $settings->get('social_saweria'),
                'sociabuzz' => $settings->get('social_sociabuzz'),
            ],
        ]);
    }
}
