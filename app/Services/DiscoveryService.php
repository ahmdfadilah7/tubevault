<?php

namespace App\Services;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DiscoveryService
{
    public function listUsers(User $viewer, int $limit = 50): Collection
    {
        return User::query()
            ->where('id', '!=', $viewer->id)
            ->withCount([
                'playlists',
                'playlists as playlists_with_items_count' => function ($query) {
                    $query->whereHas('items');
                },
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function listRecommendedPlaylists(User $viewer, int $limit = 24): Collection
    {
        return Playlist::query()
            ->where('user_id', '!=', $viewer->id)
            ->whereHas('items')
            ->with(['user:id,name,avatar'])
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function listPlaylistsForUser(User $viewer, int $userId, int $limit = 30): Collection
    {
        return Playlist::query()
            ->where('user_id', $userId)
            ->when($userId !== $viewer->id, fn ($q) => $q->whereHas('items'))
            ->with(['user:id,name,avatar'])
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}
