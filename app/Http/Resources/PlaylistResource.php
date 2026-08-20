<?php

namespace App\Http\Resources;

use App\Support\AnonymousLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Playlist */
class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'is_owner' => $request->user()?->id === $this->user_id,
            'owner_anonymous_label' => AnonymousLabel::forUser($this->user_id),
            'user' => new AnonymousMemberResource($this->whenLoaded('user')),
            'items_count' => $this->whenCounted('items'),
            'items' => PlaylistItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
