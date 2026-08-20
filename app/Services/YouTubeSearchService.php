<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeSearchService
{
    public function searchVideoId(string $query): ?string
    {
        $apiKey = config('youtube.api_key');
        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(12)
                ->get('https://www.googleapis.com/youtube/v3/search', [
                    'part' => 'snippet',
                    'q' => $query,
                    'type' => 'video',
                    'maxResults' => 5,
                    'key' => $apiKey,
                    'videoCategoryId' => '10',
                    'safeSearch' => 'none',
                ]);

            if (! $response->successful()) {
                Log::warning('YouTube Data API search failed', [
                    'status' => $response->status(),
                    'body' => $response->json('error.message'),
                ]);

                return null;
            }

            foreach ($response->json('items', []) as $item) {
                $id = $item['id']['videoId'] ?? null;
                if (is_string($id) && strlen($id) === 11) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('YouTube Data API search error', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Search YouTube suggestions with cache to save quota.
     *
     * @return array<int, array{video_id: string, title: string, channel: string, thumbnail: string, url: string}>
     */
    public function searchSuggestions(string $query, int $maxResults = 5): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $apiKey = config('youtube.api_key');
        if (! $apiKey) {
            return [];
        }

        // Cap strictly for quota control.
        $maxResults = max(1, min($maxResults, 5));
        $normalized = mb_strtolower($query);
        $cacheKey = 'yt:search:v1:'.md5($normalized.'|'.$maxResults);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($apiKey, $query, $maxResults) {
            try {
                $response = Http::timeout(10)
                    ->get('https://www.googleapis.com/youtube/v3/search', [
                        'part' => 'snippet',
                        'q' => $query,
                        'type' => 'video',
                        'maxResults' => $maxResults,
                        'key' => $apiKey,
                        'safeSearch' => 'none',
                    ]);

                if (! $response->successful()) {
                    Log::warning('YouTube search suggestions failed', [
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                    ]);

                    return [];
                }

                $items = $response->json('items') ?? [];
                if (! is_array($items)) {
                    return [];
                }

                $results = [];
                foreach ($items as $item) {
                    $videoId = $item['id']['videoId'] ?? null;
                    if (! is_string($videoId) || strlen($videoId) !== 11) {
                        continue;
                    }

                    $snippet = $item['snippet'] ?? [];
                    $thumbnail =
                        $snippet['thumbnails']['medium']['url']
                        ?? $snippet['thumbnails']['default']['url']
                        ?? '';

                    $results[] = [
                        'video_id' => $videoId,
                        'title' => (string) ($snippet['title'] ?? 'Untitled'),
                        'channel' => (string) ($snippet['channelTitle'] ?? ''),
                        'thumbnail' => (string) $thumbnail,
                        'url' => "https://www.youtube.com/watch?v={$videoId}",
                    ];
                }

                return $results;
            } catch (\Throwable $e) {
                Log::warning('YouTube search suggestions error', ['message' => $e->getMessage()]);

                return [];
            }
        });
    }
}
