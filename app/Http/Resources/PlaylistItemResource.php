<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlaylistItem */
class PlaylistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'saved_video_id' => $this->saved_video_id,
            'video' => new SavedVideoResource($this->whenLoaded('savedVideo')),
        ];
    }
}
