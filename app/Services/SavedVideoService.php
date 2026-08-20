<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\SavedVideo;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class SavedVideoService
{
    public function __construct(
        private readonly YouTubeService $youTube,
        private readonly SpotifyService $spotify,
        private readonly MediaEmbedService $embeds,
        private readonly SpotifyYouTubeResolver $spotifyYouTube,
    ) {}

    public function paginate(User $user, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = SavedVideo::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('channel_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findForUser(User $user, int $id): SavedVideo
    {
        return SavedVideo::query()
            ->where('user_id', $user->id)
            ->findOrFail($id);
    }

    public function findForUserOrPlaylist(User $user, int $id, ?int $playlistId = null): SavedVideo
    {
        if (SavedVideo::query()->where('user_id', $user->id)->whereKey($id)->exists()) {
            return $this->findForUser($user, $id);
        }

        if ($playlistId) {
            $inPlaylist = Playlist::query()
                ->whereKey($playlistId)
                ->whereHas('items', fn ($q) => $q->where('saved_video_id', $id))
                ->exists();

            if ($inPlaylist) {
                return SavedVideo::query()->findOrFail($id);
            }
        }

        return $this->findForUser($user, $id);
    }

    /**
     * @param  array{url?: string, youtube_id?: string, notes?: string|null}  $payload
     */
    public function create(User $user, array $payload): SavedVideo
    {
        $input = $payload['url'] ?? $payload['youtube_id'] ?? '';
        $source = $this->embeds->detectSource($input);

        if ($source === 'spotify') {
            return $this->createSpotify($user, $input, $payload['notes'] ?? null);
        }

        return $this->createYouTube($user, $input, $payload, $payload['notes'] ?? null);
    }

    public function update(User $user, SavedVideo $video, array $payload): SavedVideo
    {
        $this->ensureOwner($user, $video);

        $video->update([
            'notes' => $payload['notes'] ?? $video->notes,
        ]);

        return $video->fresh();
    }

    public function delete(User $user, SavedVideo $video): void
    {
        $this->ensureOwner($user, $video);
        $video->delete();
    }

    public function recordWatch(User $user, SavedVideo $video): SavedVideo
    {
        $this->ensureOwner($user, $video);

        $video->update([
            'watch_count' => $video->watch_count + 1,
            'last_watched_at' => now(),
        ]);

        return $video->fresh();
    }

    /**
     * Salin lagu dari playlist pengguna lain ke perpustakaan viewer.
     */
    public function importFromSharedPlaylist(User $user, int $sourceVideoId, int $playlistId): SavedVideo
    {
        $inPlaylist = Playlist::query()
            ->whereKey($playlistId)
            ->whereHas('items', fn ($q) => $q->where('saved_video_id', $sourceVideoId))
            ->exists();

        if (! $inPlaylist) {
            abort(404, 'Video tidak ditemukan di playlist tersebut.');
        }

        $source = SavedVideo::query()->findOrFail($sourceVideoId);

        if ($source->user_id === $user->id) {
            return $source;
        }

        return $this->duplicateForUser($user, $source);
    }

    private function duplicateForUser(User $user, SavedVideo $source): SavedVideo
    {
        if ($source->isYoutube() && $source->youtube_id) {
            $existing = SavedVideo::query()
                ->where('user_id', $user->id)
                ->where('media_type', 'youtube')
                ->where('youtube_id', $source->youtube_id)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        if ($source->isSpotify() && $source->spotify_id) {
            $existing = SavedVideo::query()
                ->where('user_id', $user->id)
                ->where('media_type', 'spotify')
                ->where('spotify_id', $source->spotify_id)
                ->where('spotify_type', $source->spotify_type)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return SavedVideo::query()->create([
            'user_id' => $user->id,
            'media_type' => $source->media_type,
            'youtube_id' => $source->youtube_id,
            'spotify_id' => $source->spotify_id,
            'spotify_type' => $source->spotify_type,
            'playback_youtube_id' => $source->playback_youtube_id,
            'title' => $source->title,
            'thumbnail_url' => $source->thumbnail_url,
            'channel_name' => $source->channel_name,
            'notes' => null,
        ]);
    }

    public function preview(?User $user, string $input): array
    {
        $source = $this->embeds->detectSource($input);

        if ($source === 'spotify') {
            return $this->previewSpotify($user, $input);
        }

        return $this->previewYouTube($user, $input);
    }

    private function ensureOwner(User $user, SavedVideo $video): void
    {
        if ($video->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke video ini.');
        }
    }

    private function createYouTube(User $user, string $input, array $payload, ?string $notes): SavedVideo
    {
        $youtubeId = isset($payload['youtube_id'])
            ? $this->youTube->extractVideoId($payload['youtube_id'])
            : $this->youTube->extractVideoId($input);

        if (SavedVideo::query()
            ->where('user_id', $user->id)
            ->where('media_type', 'youtube')
            ->where('youtube_id', $youtubeId)
            ->exists()) {
            throw new \DomainException('Video sudah ada di perpustakaan Anda.');
        }

        $meta = $this->youTube->fetchMetadata($youtubeId);

        return SavedVideo::query()->create([
            'user_id' => $user->id,
            'media_type' => 'youtube',
            'youtube_id' => $meta['youtube_id'],
            'title' => $meta['title'],
            'thumbnail_url' => $meta['thumbnail_url'],
            'channel_name' => $meta['channel_name'],
            'notes' => $notes,
        ]);
    }

    private function createSpotify(User $user, string $input, ?string $notes): SavedVideo
    {
        $uri = $this->spotify->parseUri($input);
        $meta = $this->spotify->fetchMetadata($uri['type'], $uri['id']);

        if (SavedVideo::query()
            ->where('user_id', $user->id)
            ->where('media_type', 'spotify')
            ->where('spotify_id', $uri['id'])
            ->where('spotify_type', $uri['type'])
            ->exists()) {
            throw new \DomainException('Lagu/konten Spotify ini sudah tersimpan.');
        }

        $artist = ($meta['channel_name'] ?? '') !== 'Spotify' ? $meta['channel_name'] : null;
        $playbackYoutubeId = $this->spotifyYouTube->resolve($meta['title'], $uri['type'], $artist);

        return SavedVideo::query()->create([
            'user_id' => $user->id,
            'media_type' => 'spotify',
            'spotify_id' => $uri['id'],
            'spotify_type' => $uri['type'],
            'playback_youtube_id' => $playbackYoutubeId,
            'title' => $meta['title'],
            'thumbnail_url' => $meta['thumbnail_url'],
            'channel_name' => $meta['channel_name'],
            'notes' => $notes,
        ]);
    }

    private function previewYouTube(?User $user, string $input): array
    {
        $youtubeId = $this->youTube->extractVideoId($input);
        $meta = $this->youTube->fetchMetadata($youtubeId);

        $embed = $this->youTube->buildEmbedBundle(
            $youtubeId,
            $meta['title'],
            $meta['channel_name'],
        );

        return [
            'media_type' => 'youtube',
            'youtube_id' => $meta['youtube_id'],
            'title' => $meta['title'],
            'thumbnail_url' => $meta['thumbnail_url'],
            'channel_name' => $meta['channel_name'],
            'is_music' => $meta['is_music'],
            'embed_url' => $embed['embed_url'],
            'embed_fallbacks' => $embed['embed_fallbacks'],
            'already_saved' => $user
                ? SavedVideo::query()
                    ->where('user_id', $user->id)
                    ->where('media_type', 'youtube')
                    ->where('youtube_id', $youtubeId)
                    ->exists()
                : false,
        ];
    }

    private function previewSpotify(?User $user, string $input): array
    {
        $uri = $this->spotify->parseUri($input);
        $meta = $this->spotify->fetchMetadata($uri['type'], $uri['id']);
        $artist = ($meta['channel_name'] ?? '') !== 'Spotify' ? $meta['channel_name'] : null;
        $playback = $this->embeds->forSpotify($uri['type'], $uri['id'], $meta['title'], null, $artist);

        return [
            'media_type' => 'spotify',
            'spotify_id' => $uri['id'],
            'spotify_type' => $uri['type'],
            'playback_youtube_id' => $playback['playback_youtube_id'] ?? null,
            'playback_via_youtube' => (bool) ($playback['playback_via_youtube'] ?? false),
            'player_media_type' => $playback['player_media_type'] ?? 'spotify',
            'title' => $meta['title'],
            'thumbnail_url' => $meta['thumbnail_url'],
            'channel_name' => $meta['channel_name'],
            'is_music' => true,
            'embed_url' => $playback['embed_url'],
            'embed_fallbacks' => $playback['embed_fallbacks'] ?? [],
            'external_url' => $playback['external_url'] ?? $this->spotify->buildOpenUrl($uri['type'], $uri['id']),
            'playback_notice' => $playback['playback_notice'] ?? null,
            'already_saved' => $user
                ? SavedVideo::query()
                    ->where('user_id', $user->id)
                    ->where('media_type', 'spotify')
                    ->where('spotify_id', $uri['id'])
                    ->where('spotify_type', $uri['type'])
                    ->exists()
                : false,
        ];
    }
}
