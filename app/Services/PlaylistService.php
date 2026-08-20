<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\SavedVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlaylistService
{
    public function __construct(
        private readonly SavedVideoService $videos,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $user->playlists()
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(User $user, int $playlistId): Playlist
    {
        return $user->playlists()
            ->with(['items.savedVideo', 'user:id,name,avatar'])
            ->findOrFail($playlistId);
    }

    public function findAccessible(User $viewer, int $playlistId): Playlist
    {
        return Playlist::query()
            ->with(['items.savedVideo', 'user:id,name,avatar'])
            ->findOrFail($playlistId);
    }

    public function create(User $user, array $data): Playlist
    {
        return $user->playlists()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Playlist $playlist, array $data): Playlist
    {
        $playlist->update([
            'name' => $data['name'] ?? $playlist->name,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $playlist->description,
        ]);

        return $playlist->fresh();
    }

    public function delete(Playlist $playlist): void
    {
        $playlist->delete();
    }

    /**
     * @param  array{saved_video_id?: int, url?: string}  $payload
     */
    public function addItem(User $user, Playlist $playlist, array $payload): PlaylistItem
    {
        $savedVideo = $this->resolveSavedVideo($user, $payload);

        if ($playlist->items()->where('saved_video_id', $savedVideo->id)->exists()) {
            throw new \DomainException('Video sudah ada di playlist ini.');
        }

        $position = (int) $playlist->items()->max('position') + 1;

        return $playlist->items()->create([
            'saved_video_id' => $savedVideo->id,
            'position' => $position,
        ]);
    }

    public function removeItem(Playlist $playlist, int $itemId): void
    {
        $item = $playlist->items()->findOrFail($itemId);
        $removedPosition = $item->position;
        $item->delete();

        $playlist->items()
            ->where('position', '>', $removedPosition)
            ->decrement('position');
    }

    /**
     * @param  list<int>  $itemIds  ordered playlist_item ids
     */
    public function reorder(Playlist $playlist, array $itemIds): Playlist
    {
        DB::transaction(function () use ($playlist, $itemIds) {
            foreach ($itemIds as $index => $itemId) {
                $playlist->items()
                    ->where('id', $itemId)
                    ->update(['position' => $index + 1]);
            }
        });

        return $this->findForUser($playlist->user, $playlist->id);
    }

    /**
     * @param  array{saved_video_id?: int, url?: string, shared_saved_video_id?: int, shared_playlist_id?: int}  $payload
     */
    private function resolveSavedVideo(User $user, array $payload): SavedVideo
    {
        if (! empty($payload['shared_saved_video_id'])) {
            $playlistId = (int) ($payload['shared_playlist_id'] ?? 0);
            if ($playlistId < 1) {
                throw new \InvalidArgumentException('shared_playlist_id wajib diisi.');
            }

            return $this->videos->importFromSharedPlaylist(
                $user,
                (int) $payload['shared_saved_video_id'],
                $playlistId,
            );
        }

        if (! empty($payload['saved_video_id'])) {
            return $this->videos->findForUser($user, $payload['saved_video_id']);
        }

        if (! empty($payload['url'])) {
            return $this->videos->create($user, ['url' => $payload['url']]);
        }

        throw new \InvalidArgumentException('saved_video_id, url, atau shared_saved_video_id wajib diisi.');
    }
}
