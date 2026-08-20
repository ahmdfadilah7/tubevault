<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSettingsService
{
    public const CACHE_KEY = 'site_settings.all';

    /**
     * Default branding / SEO values.
     *
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        return [
            'site_name' => 'TubeVault',
            'tagline' => 'Pemutar Musik Tanpa Iklan',
            'description' => 'TubeVault adalah website pemutar musik tanpa iklan. Simpan, putar, dan kelola lagu dari YouTube & Spotify dalam satu perpustakaan pribadi.',
            'keywords' => 'TubeVault, pemutar musik tanpa iklan, YouTube, Spotify, playlist, HachieCode',
            'author' => 'HachieCode',
            'creator' => '@hachiecode',
            'theme_color' => '#07080c',
            'contact_email' => 'noreply@hachiedigitation.com',
            'footer_text' => 'Dibuat oleh HachieCode',
            'logo_path' => null,
            'favicon_path' => null,
            'og_image_path' => null,
            'social_tiktok' => 'https://www.tiktok.com/@hachiecode',
            'social_saweria' => 'https://saweria.co/hachiecode',
            'social_sociabuzz' => 'https://sociabuzz.com/hachiecode/tribe',
            'twitter_handle' => '@hachiecode',
            'google_site_verification' => 'FH0FiZEtzGi1yRsQKj4mTAxOPsc1wbCHlGbWUdb59hY',
            'maintenance_mode' => '0',
            'maintenance_message' => 'TubeVault sedang dalam perawatan. Silakan kembali lagi nanti.',
            'allow_registration' => '1',
            'spa_js' => '/assets/index-Dr6uby78.js',
            'spa_css' => '/assets/index-x6lhkfnV.css',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = SiteSetting::query()->pluck('value', 'key')->all();

            return array_merge($this->defaults(), $stored);
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? ($this->defaults()[$key] ?? null);
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key, '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $this->defaults())) {
                continue;
            }

            if ($value instanceof UploadedFile) {
                continue;
            }

            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '')]
            );
        }

        $this->forgetCache();
    }

    public function storeUpload(string $key, UploadedFile $file, string $folder): string
    {
        $previous = $this->get($key);
        $path = $file->store("branding/{$folder}", 'public');

        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $path]
        );

        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        $this->forgetCache();

        return $path;
    }

    public function removeFile(string $key): void
    {
        $previous = $this->get($key);

        if (is_string($previous) && $previous !== '') {
            Storage::disk('public')->delete($previous);
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => null]
        );

        $this->forgetCache();
    }

    public function publicUrl(?string $path, ?string $fallback = null): ?string
    {
        if (is_string($path) && $path !== '') {
            return Storage::disk('public')->url($path);
        }

        return $fallback;
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->get('logo_path'));
    }

    public function faviconUrl(): string
    {
        return $this->publicUrl($this->get('favicon_path'), '/favicon.svg') ?? '/favicon.svg';
    }

    public function ogImageUrl(): string
    {
        return $this->publicUrl($this->get('og_image_path'), '/og-image.png') ?? '/og-image.png';
    }

    public function siteTitle(): string
    {
        $name = (string) $this->get('site_name', 'TubeVault');
        $tagline = (string) $this->get('tagline', '');

        return $tagline !== '' ? "{$name} — {$tagline}" : $name;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Detect SPA asset filenames from public/index.html if present.
     *
     * @return array{js: string, css: string}
     */
    public function detectSpaAssets(): array
    {
        $index = public_path('index.html');
        $js = (string) $this->get('spa_js', '/assets/index-Dr6uby78.js');
        $css = (string) $this->get('spa_css', '/assets/index-x6lhkfnV.css');

        if (! is_file($index)) {
            return compact('js', 'css');
        }

        $html = file_get_contents($index) ?: '';

        if (preg_match('/src="(\/assets\/[^"]+\.js)"/', $html, $m)) {
            $js = $m[1];
        }

        if (preg_match('/href="(\/assets\/[^"]+\.css)"/', $html, $m)) {
            $css = $m[1];
        }

        return compact('js', 'css');
    }
}
