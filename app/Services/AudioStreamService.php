<?php

namespace App\Services;

use App\Models\SavedVideo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AudioStreamService
{
    public function __construct(
        private readonly SpotifyYouTubeResolver $spotifyYouTube,
    ) {}

    /**
     * @return array{url: string, mime_type: string, youtube_id: string, source?: string}|null
     */
    public function forSavedVideo(SavedVideo $video): ?array
    {
        $youtubeId = $this->resolveYoutubeId($video);
        if (! $youtubeId) {
            return null;
        }

        return $this->resolveForYoutubeId($youtubeId);
    }

    public function resolveYoutubeId(SavedVideo $video): ?string
    {
        foreach ([$video->youtube_id, $video->playback_youtube_id] as $candidate) {
            $id = $this->normalizeYoutubeId($candidate);
            if ($id) {
                return $id;
            }
        }

        if ($video->media_type === 'spotify' && $video->spotify_id) {
            $type = $video->spotify_type ?? 'track';
            if ($this->spotifyYouTube->canResolveType($type)) {
                $artist = $video->channel_name !== 'Spotify' ? $video->channel_name : null;
                $id = $this->spotifyYouTube->resolve($video->title, $type, $artist);
                if ($id) {
                    $video->update(['playback_youtube_id' => $id]);

                    return $id;
                }
            }
        }

        return null;
    }

    private function normalizeYoutubeId(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $value)) {
            return $value;
        }

        if (preg_match('/(?:youtu\.be\/|v=|\/embed\/|\/shorts\/)([a-zA-Z0-9_-]{11})/', $value, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string, youtube_id: string, source?: string}|null
     */
    public function resolveForYoutubeId(string $youtubeId): ?array
    {
        foreach (config('youtube.audio_stream_sources', ['piped', 'invidious', 'cobalt']) as $source) {
            $stream = match ($source) {
                'piped' => $this->fetchFromPipedInstances($youtubeId),
                'invidious' => $this->fetchFromInvidiousInstances($youtubeId),
                'cobalt' => $this->fetchFromCobaltInstances($youtubeId),
                default => null,
            };

            if ($stream) {
                return array_merge($stream, [
                    'youtube_id' => $youtubeId,
                    'source' => $source,
                ]);
            }
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromPipedInstances(string $youtubeId): ?array
    {
        foreach (config('youtube.piped_instances', []) as $baseUrl) {
            $stream = $this->fetchFromPiped($baseUrl, $youtubeId);
            if ($stream) {
                return $stream;
            }
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromPiped(string $baseUrl, string $youtubeId): ?array
    {
        $baseUrl = rtrim($baseUrl, '/');

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->get("{$baseUrl}/streams/{$youtubeId}");

            if (! $response->successful()) {
                return null;
            }

            $streams = $response->json('audioStreams') ?? [];
            if (! is_array($streams) || $streams === []) {
                return null;
            }

            usort($streams, fn ($a, $b) => ($b['bitrate'] ?? 0) <=> ($a['bitrate'] ?? 0));

            foreach ($streams as $stream) {
                $url = $stream['url'] ?? null;
                if (is_string($url) && $url !== '') {
                    return [
                        'url' => $url,
                        'mime_type' => $stream['mimeType'] ?? 'audio/mp4',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Piped audio stream failed', [
                'instance' => $baseUrl,
                'youtube_id' => $youtubeId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromInvidiousInstances(string $youtubeId): ?array
    {
        foreach (config('youtube.invidious_instances', []) as $baseUrl) {
            $stream = $this->fetchFromInvidious($baseUrl, $youtubeId);
            if ($stream) {
                return $stream;
            }
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromInvidious(string $baseUrl, string $youtubeId): ?array
    {
        $baseUrl = rtrim($baseUrl, '/');

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get("{$baseUrl}/api/v1/videos/{$youtubeId}");

            if (! $response->successful()) {
                return null;
            }

            $formats = $response->json('adaptiveFormats') ?? [];
            if (! is_array($formats) || $formats === []) {
                return null;
            }

            $audioFormats = array_values(array_filter($formats, function ($format) {
                if (! is_array($format)) {
                    return false;
                }

                $type = (string) ($format['type'] ?? '');
                $url = $format['url'] ?? null;

                return is_string($url) && $url !== '' && (
                    str_starts_with($type, 'audio/')
                    || ! empty($format['audioQuality'])
                );
            }));

            if ($audioFormats === []) {
                return null;
            }

            usort($audioFormats, function ($a, $b) {
                return (int) ($b['bitrate'] ?? 0) <=> (int) ($a['bitrate'] ?? 0);
            });

            $best = $audioFormats[0];
            $type = (string) ($best['type'] ?? 'audio/mp4');
            $mime = trim(explode(';', $type)[0]) ?: 'audio/mp4';

            return [
                'url' => $best['url'],
                'mime_type' => $mime,
            ];
        } catch (\Throwable $e) {
            Log::debug('Invidious audio stream failed', [
                'instance' => $baseUrl,
                'youtube_id' => $youtubeId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromCobaltInstances(string $youtubeId): ?array
    {
        foreach (config('youtube.cobalt_instances', []) as $baseUrl) {
            $stream = $this->fetchFromCobalt($baseUrl, $youtubeId);
            if ($stream) {
                return $stream;
            }
        }

        return null;
    }

    /**
     * Resolve audio for download. Prefer Cobalt MP3 when possible.
     *
     * @return array{url: string, mime_type: string, youtube_id: string, source?: string, format: string, extension: string}|null
     */
    public function downloadForSavedVideo(SavedVideo $video, string $preferFormat = 'mp3'): ?array
    {
        $youtubeId = $this->resolveYoutubeId($video);
        if (! $youtubeId) {
            return null;
        }

        $preferFormat = strtolower($preferFormat);

        if ($preferFormat === 'mp3') {
            $mp3 = $this->fetchMp3FromCobaltInstances($youtubeId);
            if ($mp3) {
                return array_merge($mp3, [
                    'youtube_id' => $youtubeId,
                    'source' => 'cobalt',
                    'format' => 'mp3',
                    'extension' => 'mp3',
                ]);
            }
        }

        $stream = $this->resolveForYoutubeId($youtubeId);
        if (! $stream) {
            return null;
        }

        $mime = strtolower((string) ($stream['mime_type'] ?? 'audio/mp4'));
        $extension = match (true) {
            str_contains($mime, 'mpeg') || str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            default => 'm4a',
        };

        return array_merge($stream, [
            'format' => $extension === 'mp3' ? 'mp3' : 'audio',
            'extension' => $extension,
        ]);
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchMp3FromCobaltInstances(string $youtubeId): ?array
    {
        foreach (config('youtube.cobalt_instances', []) as $baseUrl) {
            $stream = $this->fetchFromCobalt($baseUrl, $youtubeId, 'mp3');
            if ($stream) {
                return [
                    'url' => $stream['url'],
                    'mime_type' => 'audio/mpeg',
                ];
            }
        }

        return null;
    }

    /**
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromCobalt(string $baseUrl, string $youtubeId, string $audioFormat = 'best'): ?array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $watchUrl = "https://www.youtube.com/watch?v={$youtubeId}";
        $apiKey = trim((string) config('youtube.cobalt_api_key', ''));

        try {
            // Cobalt menolak Accept selain application/json; jangan pakai acceptJson()/asJson() Laravel
            // karena sering memicu error.api.header.accept. api.cobalt.tools juga butuh JWT.
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if ($apiKey !== '') {
                $headers['Authorization'] = str_starts_with($apiKey, 'Bearer ')
                    ? $apiKey
                    : 'Bearer '.$apiKey;
            }

            $response = Http::timeout(45)
                ->withHeaders($headers)
                ->withBody(json_encode([
                    'url' => $watchUrl,
                    'downloadMode' => 'audio',
                    'audioFormat' => $audioFormat,
                    'youtubeVideoCodec' => 'h264',
                ], JSON_UNESCAPED_SLASHES), 'application/json')
                ->post("{$baseUrl}/");

            if (! $response->successful()) {
                Log::debug('Cobalt audio stream rejected', [
                    'instance' => $baseUrl,
                    'youtube_id' => $youtubeId,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);

                return null;
            }

            $status = $response->json('status');
            $url = $response->json('url');

            if (! in_array($status, ['redirect', 'tunnel'], true) || ! is_string($url) || $url === '') {
                return null;
            }

            $mime = $audioFormat === 'mp3' ? 'audio/mpeg' : 'audio/mp4';

            return [
                'url' => $url,
                'mime_type' => $mime,
            ];
        } catch (\Throwable $e) {
            Log::debug('Cobalt audio stream failed', [
                'instance' => $baseUrl,
                'youtube_id' => $youtubeId,
                'audio_format' => $audioFormat,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
