<?php

namespace App\Http\Resources;

use App\Support\AnonymousLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class AnonymousMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'anonymous_label' => AnonymousLabel::forUser($this->id),
            'playlists_count' => $this->whenCounted('playlists'),
            'playlists_with_items_count' => $this->when(
                isset($this->playlists_with_items_count),
                (int) $this->playlists_with_items_count,
            ),
        ];
    }
}
