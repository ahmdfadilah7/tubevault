<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    @php
      $siteName = $settings['site_name'] ?? 'TubeVault';
      $tagline = $settings['tagline'] ?? 'Pemutar Musik Tanpa Iklan';
      $description = $settings['description'] ?? '';
      $keywords = $settings['keywords'] ?? '';
      $author = $settings['author'] ?? 'HachieCode';
      $creator = $settings['creator'] ?? '@hachiecode';
      $theme = $settings['theme_color'] ?? '#07080c';
      $title = $tagline ? "{$siteName} — {$tagline}" : $siteName;
      $base = rtrim(config('app.url'), '/');
      $favicon = $faviconUrl ?? '/favicon.svg';
      $og = $ogImageUrl ?? '/og-image.png';
      $logo = $logoUrl ?? $favicon;
      $ogAbs = str_starts_with($og, 'http') ? $og : $base.$og;
      $faviconAbs = str_starts_with($favicon, 'http') ? $favicon : $base.$favicon;
      $logoAbs = str_starts_with($logo, 'http') ? $logo : $base.$logo;
      $sameAs = array_values(array_filter([
          $settings['social_tiktok'] ?? null,
          $settings['social_saweria'] ?? null,
          $settings['social_sociabuzz'] ?? null,
      ]));
      $assets = app(\App\Services\SiteSettingsService::class)->detectSpaAssets();
    @endphp

    <link rel="icon" href="{{ $favicon }}" />
    <link rel="apple-touch-icon" href="{{ $favicon }}" />
    @if (!empty($logoUrl))
      <link rel="shortcut icon" href="{{ $favicon }}" />
    @endif
    <link rel="manifest" href="/manifest.webmanifest" />
    <link rel="canonical" href="{{ $base }}/" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ $base }}/sitemap.xml" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="{{ $theme }}" />
    <meta name="color-scheme" content="dark" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}" />
    <meta name="format-detection" content="telephone=no" />

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}" />
    <meta name="keywords" content="{{ $keywords }}" />
    <meta name="author" content="{{ $author }}" />
    <meta name="creator" content="{{ $creator }}" />
    <meta name="publisher" content="{{ $author }}" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    <meta name="googlebot" content="index, follow" />
    <meta name="bingbot" content="index, follow" />
    @if (!empty($settings['google_site_verification']))
      <meta name="google-site-verification" content="{{ $settings['google_site_verification'] }}" />
    @endif
    <meta name="application-name" content="{{ $siteName }}" />
    <meta name="referrer" content="strict-origin-when-cross-origin" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ $siteName }}" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:url" content="{{ $base }}/" />
    <meta property="og:image" content="{{ $ogAbs }}" />
    <meta property="og:image:alt" content="{{ $title }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $title }}" />
    <meta name="twitter:description" content="{{ $description }}" />
    <meta name="twitter:image" content="{{ $ogAbs }}" />
    <meta name="twitter:creator" content="{{ $settings['twitter_handle'] ?? $creator }}" />

    <script type="application/ld+json">
      {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
          [
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'url' => $base.'/',
            'name' => $siteName,
            'description' => $description,
            'inLanguage' => 'id',
            'publisher' => ['@id' => $base.'/#organization'],
          ],
          [
            '@type' => 'Organization',
            '@id' => $base.'/#organization',
            'name' => $author,
            'url' => $base.'/',
            'logo' => ['@type' => 'ImageObject', 'url' => $logoAbs],
            'sameAs' => $sameAs,
          ],
          [
            '@type' => 'WebApplication',
            '@id' => $base.'/#app',
            'name' => $siteName,
            'url' => $base.'/',
            'applicationCategory' => 'MultimediaApplication',
            'operatingSystem' => 'Web',
            'description' => $description,
            'image' => $ogAbs,
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
          ],
        ],
      ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
    </script>

    <script type="module" crossorigin src="{{ $assets['js'] }}"></script>
    <link rel="stylesheet" crossorigin href="{{ $assets['css'] }}">
  </head>
  <body>
    <noscript>
      <main>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <p>{{ $settings['footer_text'] ?? '' }}</p>
      </main>
    </noscript>
    <div id="app"></div>
    <script src="/js/mp3-download.js" defer></script>
  </body>
</html>
