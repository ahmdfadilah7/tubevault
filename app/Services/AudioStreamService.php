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
        if ($video->media_type === 'youtube' && $video->youtube_id) {
            return $video->youtube_id;
        }

        if ($video->playback_youtube_id) {
            return $video->playback_youtube_id;
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
     * @return array{url: string, mime_type: string}|null
     */
    private function fetchFromCobalt(string $baseUrl, string $youtubeId): ?array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $watchUrl = "https://www.youtube.com/watch?v={$youtubeId}";

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post("{$baseUrl}/", [
                    'url' => $watchUrl,
                    'downloadMode' => 'audio',
                    'audioFormat' => 'best',
                    'youtubeVideoCodec' => 'h264',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $status = $response->json('status');
            $url = $response->json('url');

            if (! in_array($status, ['redirect', 'tunnel'], true) || ! is_string($url) || $url === '') {
                return null;
            }

            return [
                'url' => $url,
                'mime_type' => 'audio/mp4',
            ];
        } catch (\Throwable $e) {
            Log::debug('Cobalt audio stream failed', [
                'instance' => $baseUrl,
                'youtube_id' => $youtubeId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
