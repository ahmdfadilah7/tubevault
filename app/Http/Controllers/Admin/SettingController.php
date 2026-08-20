<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private SiteSettingsService $settings) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => $this->settings->all(),
            'logoUrl' => $this->settings->logoUrl(),
            'faviconUrl' => $this->settings->faviconUrl(),
            'ogImageUrl' => $this->settings->ogImageUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:120'],
            'creator' => ['nullable', 'string', 'max:120'],
            'theme_color' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'social_tiktok' => ['nullable', 'url', 'max:255'],
            'social_saweria' => ['nullable', 'url', 'max:255'],
            'social_sociabuzz' => ['nullable', 'url', 'max:255'],
            'twitter_handle' => ['nullable', 'string', 'max:80'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'allow_registration' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,svg,jpg,jpeg,webp', 'max:1024'],
            'og_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        $this->settings->putMany([
            'site_name' => $data['site_name'],
            'tagline' => $data['tagline'] ?? '',
            'description' => $data['description'] ?? '',
            'keywords' => $data['keywords'] ?? '',
            'author' => $data['author'] ?? '',
            'creator' => $data['creator'] ?? '',
            'theme_color' => $data['theme_color'] ?? '#07080c',
            'contact_email' => $data['contact_email'] ?? '',
            'footer_text' => $data['footer_text'] ?? '',
            'social_tiktok' => $data['social_tiktok'] ?? '',
            'social_saweria' => $data['social_saweria'] ?? '',
            'social_sociabuzz' => $data['social_sociabuzz'] ?? '',
            'twitter_handle' => $data['twitter_handle'] ?? '',
            'google_site_verification' => $data['google_site_verification'] ?? '',
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'maintenance_message' => $data['maintenance_message'] ?? '',
            'allow_registration' => $request->boolean('allow_registration'),
        ]);

        if ($request->boolean('remove_logo')) {
            $this->settings->removeFile('logo_path');
        } elseif ($request->hasFile('logo')) {
            $this->settings->storeUpload('logo_path', $request->file('logo'), 'logo');
        }

        if ($request->boolean('remove_favicon')) {
            $this->settings->removeFile('favicon_path');
        } elseif ($request->hasFile('favicon')) {
            $this->settings->storeUpload('favicon_path', $request->file('favicon'), 'favicon');
        }

        if ($request->boolean('remove_og_image')) {
            $this->settings->removeFile('og_image_path');
        } elseif ($request->hasFile('og_image')) {
            $this->settings->storeUpload('og_image_path', $request->file('og_image'), 'og');
        }

        return back()->with('success', 'Pengaturan website berhasil disimpan.');
    }
}
