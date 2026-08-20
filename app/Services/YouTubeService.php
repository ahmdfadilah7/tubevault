<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class YouTubeService
{
    private const ID_PATTERN = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';

    public function extractVideoId(string $input): string
    {
        $input = trim($input);

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }

        if (preg_match(self::ID_PATTERN, $input, $matches)) {
            return $matches[1];
        }

        throw new InvalidArgumentException('URL atau ID YouTube tidak valid.');
    }

    /**
     * @return array{
     *   media_type: string,
     *   youtube_id: string,
     *   title: string,
     *   thumbnail_url: string,
     *   channel_name: string|null,
     *   is_music: bool
     * }
     */
    public function fetchMetadata(string $youtubeId): array
    {
        $watchUrl = "https://www.youtube.com/watch?v={$youtubeId}";

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get(config('youtube.oembed_url'), [
                    'url' => $watchUrl,
                    'format' => 'json',
                ])
                ->throw();
        } catch (RequestException) {
            throw new InvalidArgumentException('Video tidak ditemukan atau tidak dapat diakses.');
        }

        $data = $response->json();
        $title = $data['title'] ?? 'Untitled';
        $channel = $data['author_name'] ?? null;

        return [
            'media_type' => 'youtube',
            'youtube_id' => $youtubeId,
            'title' => $title,
            'thumbnail_url' => $this->resolveThumbnail($youtubeId, $data['thumbnail_url'] ?? null),
            'channel_name' => $channel,
            'is_music' => $this->isLikelyMusic($title, $channel),
        ];
    }

    /**
     * @return array{embed_url: string, embed_fallbacks: list<string>}
     */
    public function buildEmbedBundle(
        string $youtubeId,
        ?string $title = null,
        ?string $channelName = null,
    ): array {
        $isMusic = $this->isLikelyMusic($title ?? '', $channelName);

        $candidates = [
            $this->buildNocookieUrl($youtubeId),
        ];

        if (config('youtube.piped_as_fallback')) {
            $candidates[] = $this->buildPipedUrl($youtubeId, $isMusic);
        }

        if (config('youtube.allow_invidious_embed')) {
            $candidates[] = $this->buildInvidiousUrl($youtubeId, $isMusic);
        }

        $unique = array_values(array_unique(array_filter($candidates)));

        return [
            'embed_url' => $unique[0],
            'embed_fallbacks' => array_slice($unique, 1),
        ];
    }

    public function buildEmbedUrl(
        string $youtubeId,
        ?string $title = null,
        ?string $channelName = null,
    ): string {
        return $this->buildEmbedBundle($youtubeId, $title, $channelName)['embed_url'];
    }

    /**
     * Pemutaran musik / padanan Spotify→YouTube: YouTube nocookie dulu (tanpa login Piped).
     * Piped hanya opsional sebagai fallback terakhir jika diaktifkan di .env.
     *
     * @return array{embed_url: string, embed_fallbacks: list<string>}
     */
    public function buildMusicPlaybackBundle(
        string $youtubeId,
        ?string $title = null,
        ?string $channelName = null,
    ): array {
        $candidates = [
            $this->buildNocookieUrl($youtubeId),
        ];

        if (config('youtube.piped_as_fallback') || config('spotify.prefer_piped_for_playback', false)) {
            $candidates[] = $this->buildPipedUrl($youtubeId, true);
        }

        if (config('youtube.allow_invidious_embed')) {
            $candidates[] = $this->buildInvidiousUrl($youtubeId, true);
        }

        $unique = array_values(array_unique(array_filter($candidates)));

        return [
            'embed_url' => $unique[0],
            'embed_fallbacks' => array_slice($unique, 1),
        ];
    }

    public function isLikelyMusic(?string $title, ?string $channelName): bool
    {
        $haystack = strtolower(trim(($title ?? '').' '.($channelName ?? '')));

        if ($haystack === '') {
            return false;
        }

        if (str_contains($haystack, ' - topic')) {
            return true;
        }

        foreach (config('youtube.music_keywords', []) as $keyword) {
            if (str_contains($haystack, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function buildNocookieUrl(string $youtubeId): string
    {
        $params = http_build_query([
            'autoplay' => 0,
            'rel' => 0,
            'modestbranding' => 1,
            'iv_load_policy' => 3,
            'playsinline' => 1,
            'enablejsapi' => 1,
            'origin' => rtrim(env('FRONTEND_URL', config('app.url')), '/'),
        ]);

        return "https://www.youtube-nocookie.com/embed/{$youtubeId}?{$params}";
    }

    private function buildPipedUrl(string $youtubeId, bool $isMusic): string
    {
        $params = [
            'autoplay' => 0,
            'rel' => 0,
        ];

        if ($isMusic && config('youtube.use_listen_mode_for_music')) {
            $params['listen'] = 1;
        }

        $base = config('youtube.piped_base_url');

        return "{$base}/embed/{$youtubeId}?".http_build_query($params);
    }

    private function buildInvidiousUrl(string $youtubeId, bool $isMusic): string
    {
        $params = [
            'autoplay' => 0,
            'rel' => 0,
            'modestbranding' => 1,
            'local' => $isMusic ? 1 : 0,
        ];

        $base = config('youtube.invidious_base_url');

        return "{$base}/embed/{$youtubeId}?".http_build_query($params);
    }

    private function resolveThumbnail(string $youtubeId, ?string $oembedThumbnail): string
    {
        if ($oembedThumbnail) {
            return $oembedThumbnail;
        }

        return "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg";
    }
}
