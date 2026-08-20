<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'playlists_count' => $this->whenCounted('playlists'),
            'playlists_with_items_count' => $this->when(
                isset($this->playlists_with_items_count),
                (int) $this->playlists_with_items_count,
            ),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
