<?php

namespace App\Http\Resources;

use App\Services\MediaEmbedService;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SavedVideo */
class SavedVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $embeds = app(MediaEmbedService::class);
        $youTube = app(YouTubeService::class);
        $embed = $embeds->forSavedVideo($this->resource);

        $isMusic = $this->isSpotify() || $youTube->isLikelyMusic($this->title, $this->channel_name);

        return [
            'id' => $this->id,
            'media_type' => $this->media_type,
            'youtube_id' => $this->youtube_id,
            'spotify_id' => $this->spotify_id,
            'spotify_type' => $this->spotify_type,
            'playback_youtube_id' => $embed['playback_youtube_id'] ?? $this->playback_youtube_id,
            'playback_via_youtube' => (bool) ($embed['playback_via_youtube'] ?? false),
            'player_media_type' => $embed['player_media_type'] ?? ($this->isSpotify() ? 'spotify' : 'youtube'),
            'title' => $this->title,
            'thumbnail_url' => $this->thumbnail_url,
            'channel_name' => $this->channel_name,
            'notes' => $this->notes,
            'watch_count' => $this->watch_count,
            'last_watched_at' => $this->last_watched_at?->toIso8601String(),
            'is_music' => $isMusic,
            'embed_url' => $embed['embed_url'],
            'embed_fallbacks' => $embed['embed_fallbacks'],
            'external_url' => $embed['external_url'] ?? null,
            'playback_notice' => $embed['playback_notice'] ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
