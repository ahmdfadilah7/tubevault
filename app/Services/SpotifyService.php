<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SpotifyService
{
    /** Mendukung /track/… dan /intl-id/track/… (locale Spotify). */
    private const URI_PATTERN = '/spotify\.com(?:\/[a-z][a-z0-9-]*)?\/(track|album|playlist|episode|show)\/([a-zA-Z0-9]+)/i';

    /**
     * @return array{type: string, id: string}
     */
    public function parseUri(string $input): array
    {
        $input = trim($input);
        $input = strtok($input, '?') ?: $input;
        $input = strtok($input, '#') ?: $input;

        if (preg_match('/^spotify:(track|album|playlist|episode|show):([a-zA-Z0-9]+)$/i', $input, $m)) {
            return ['type' => strtolower($m[1]), 'id' => $m[2]];
        }

        if (preg_match(self::URI_PATTERN, $input, $m)) {
            return ['type' => strtolower($m[1]), 'id' => $m[2]];
        }

        throw new InvalidArgumentException('URL atau URI Spotify tidak valid.');
    }

    /**
     * @return array{
     *   media_type: string,
     *   spotify_id: string,
     *   spotify_type: string,
     *   title: string,
     *   thumbnail_url: string,
     *   channel_name: string|null
     * }
     */
    public function fetchMetadata(string $type, string $id): array
    {
        $pageUrl = "https://open.spotify.com/{$type}/{$id}";

        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; TubeVault/1.0)',
                'Accept' => 'application/json',
            ])
            ->get(config('spotify.oembed_url'), ['url' => $pageUrl]);

        $meta = [
            'media_type' => 'spotify',
            'spotify_id' => $id,
            'spotify_type' => $type,
            'title' => $this->fallbackTitle($type),
            'thumbnail_url' => $this->fallbackThumbnail(),
            'channel_name' => null,
        ];

        if ($response->successful()) {
            $data = $response->json();
            $thumbnail = $data['thumbnail_url'] ?? null;

            if (! $thumbnail && preg_match('/background-image: url\(&quot;([^&]+)&quot;\)/', $data['html'] ?? '', $m)) {
                $thumbnail = html_entity_decode($m[1]);
            }

            $meta['title'] = $data['title'] ?? $meta['title'];
            $meta['thumbnail_url'] = $thumbnail ?? $meta['thumbnail_url'];
        }

        return $this->enrichFromOpenPage($type, $id, $meta);
    }

    /**
     * oEmbed Spotify sering hanya mengembalikan judul lagu tanpa artis — ambil dari halaman open.
     *
     * @param  array{media_type: string, spotify_id: string, spotify_type: string, title: string, thumbnail_url: string, channel_name: string|null}  $meta
     * @return array{media_type: string, spotify_id: string, spotify_type: string, title: string, thumbnail_url: string, channel_name: string|null}
     */
    private function enrichFromOpenPage(string $type, string $id, array $meta): array
    {
        try {
            $html = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                ])
                ->get("https://open.spotify.com/{$type}/{$id}")
                ->body();

            if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
                $parsed = $this->parseOpenPageTitle(html_entity_decode(trim($m[1])));

                if ($parsed['title'] !== '') {
                    $meta['title'] = $parsed['title'];
                }
                if ($parsed['artist'] !== '') {
                    $meta['channel_name'] = $parsed['artist'];
                }
            }
        } catch (\Throwable) {
            // Tetap pakai metadata oEmbed jika scrape gagal
        }

        if ($meta['channel_name'] === null) {
            $meta['channel_name'] = 'Spotify';
        }

        return $meta;
    }

    /**
     * @return array{title: string, artist: string}
     */
    private function parseOpenPageTitle(string $pageTitle): array
    {
        $pageTitle = preg_replace('/\s+/u', ' ', trim($pageTitle)) ?? $pageTitle;

        if (preg_match('/^(.+?)\s*-\s*song and lyrics by\s+(.+?)\s*\|\s*Spotify$/iu', $pageTitle, $m)) {
            return ['title' => trim($m[1]), 'artist' => trim($m[2])];
        }

        if (preg_match('/^(.+?)\s*-\s*(.+?)\s*\|\s*Spotify$/iu', $pageTitle, $m)) {
            return ['title' => trim($m[1]), 'artist' => trim($m[2])];
        }

        return ['title' => '', 'artist' => ''];
    }

    public function buildEmbedUrl(string $type, string $id): string
    {
        $params = http_build_query([
            'utm_source' => 'generator',
            'theme' => 0,
        ]);

        $base = config('spotify.embed_base_url');

        return "{$base}/{$type}/{$id}?{$params}";
    }

    public function buildOpenUrl(string $type, string $id): string
    {
        return "https://open.spotify.com/{$type}/{$id}";
    }

    public function playbackNoticeForType(string $type): string
    {
        if (in_array($type, ['album', 'playlist', 'show'], true)) {
            return 'Album/playlist Spotify: simpan trek individual untuk putar penuh, atau buka di aplikasi Spotify.';
        }

        if (! config('youtube.api_key')) {
            return 'Padanan YouTube tidak ditemukan. Tambahkan YOUTUBE_API_KEY di server (Google Cloud → YouTube Data API v3) untuk hasil lebih akurat.';
        }

        return 'Padanan YouTube tidak ditemukan. Coba simpan URL trek Spotify lain, atau buka di Spotify.';
    }

    private function fallbackTitle(string $type): string
    {
        return match ($type) {
            'track' => 'Lagu Spotify',
            'album' => 'Album Spotify',
            'playlist' => 'Playlist Spotify',
            'episode' => 'Episode Podcast',
            'show' => 'Podcast Spotify',
            default => 'Konten Spotify',
        };
    }

    private function fallbackThumbnail(): string
    {
        return 'https://open.spotifycdn.com/cdn/images/favicon32.b64abd03.png';
    }
}
