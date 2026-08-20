<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpotifyYouTubeResolver
{
    public function __construct(
        private readonly YouTubeService $youTube,
        private readonly YouTubeSearchService $youTubeSearch,
    ) {}

    public function canResolveType(string $spotifyType): bool
    {
        if (! config('spotify.playback_via_youtube', true)) {
            return false;
        }

        return in_array($spotifyType, config('spotify.youtube_resolve_types', ['track', 'episode']), true);
    }

    public function resolve(string $title, string $spotifyType, ?string $artist = null): ?string
    {
        if (! $this->canResolveType($spotifyType)) {
            return null;
        }

        foreach ($this->buildSearchQueries($title, $artist) as $query) {
            $id = $this->youTubeSearch->searchVideoId($query);
            if ($id) {
                return $id;
            }

            $id = $this->searchViaYouTubeHtml($query);
            if ($id) {
                return $id;
            }

            foreach (config('spotify.piped_search_instances', []) as $baseUrl) {
                foreach (['videos', 'music_songs'] as $filter) {
                    $id = $this->searchViaPiped($baseUrl, $query, $filter);
                    if ($id) {
                        return $id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function buildSearchQueries(string $title, ?string $artist): array
    {
        $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $artist = trim(html_entity_decode($artist ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
        $title = str_replace([' · ', ' | '], ' ', $title);

        $queries = [];

        if ($artist !== '' && $title !== '') {
            $queries[] = "{$title} {$artist} official audio";
            $queries[] = "{$artist} {$title} official audio";
            $queries[] = "{$title} {$artist}";
        }

        if ($title !== '') {
            $queries[] = "{$title} official audio";
            $queries[] = $title;
        }

        return array_values(array_unique(array_filter($queries)));
    }

    private function searchViaYouTubeHtml(string $query): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get('https://www.youtube.com/results', ['search_query' => $query]);

            if (! $response->successful()) {
                return null;
            }

            if (preg_match('/"videoId":"([a-zA-Z0-9_-]{11})"/', $response->body(), $m)) {
                return $m[1];
            }
        } catch (\Throwable $e) {
            Log::debug('YouTube HTML search failed', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function searchViaPiped(string $baseUrl, string $query, string $filter): ?string
    {
        $baseUrl = rtrim($baseUrl, '/');

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$baseUrl}/search", [
                    'q' => $query,
                    'filter' => $filter,
                ]);

            if (! $response->successful()) {
                return null;
            }

            foreach ($response->json('items', []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $id = $this->extractIdFromItem($item);
                if ($id) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
            Log::debug('Piped search failed', [
                'instance' => $baseUrl,
                'filter' => $filter,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractIdFromItem(array $item): ?string
    {
        if (! empty($item['id']) && is_string($item['id']) && strlen($item['id']) === 11) {
            return $item['id'];
        }

        $url = $item['url'] ?? $item['videoId'] ?? null;
        if (! is_string($url) || $url === '') {
            return null;
        }

        try {
            return $this->youTube->extractVideoId($url);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
