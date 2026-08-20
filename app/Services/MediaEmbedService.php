<?php

namespace App\Services;

use App\Models\SavedVideo;

class MediaEmbedService
{
    public function __construct(
        private readonly YouTubeService $youTube,
        private readonly SpotifyService $spotify,
        private readonly SpotifyYouTubeResolver $spotifyYouTube,
    ) {}

    /**
     * @return array{
     *   embed_url: string,
     *   embed_fallbacks: list<string>,
     *   external_url?: string,
     *   playback_notice?: string|null,
     *   playback_youtube_id?: string|null,
     *   playback_via_youtube?: bool,
     *   player_media_type?: string
     * }
     */
    public function forSavedVideo(SavedVideo $video): array
    {
        if ($video->media_type === 'spotify' && $video->spotify_id) {
            $type = $video->spotify_type ?? 'track';
            $playbackId = $video->playback_youtube_id;

            if (! $playbackId && $this->spotifyYouTube->canResolveType($type)) {
                $artist = $video->channel_name !== 'Spotify' ? $video->channel_name : null;
                $title = $video->title;

                if ($artist === null) {
                    $fresh = $this->spotify->fetchMetadata($type, $video->spotify_id);
                    $title = $fresh['title'] ?? $title;
                    $artist = ($fresh['channel_name'] ?? '') !== 'Spotify' ? $fresh['channel_name'] : null;
                }

                $playbackId = $this->spotifyYouTube->resolve($title, $type, $artist);
                if ($playbackId) {
                    $video->update(['playback_youtube_id' => $playbackId]);
                }
            }

            return $this->forSpotify(
                $type,
                $video->spotify_id,
                $video->title,
                $playbackId,
                $video->channel_name !== 'Spotify' ? $video->channel_name : null,
            );
        }

        $bundle = $this->youTube->buildEmbedBundle(
            $video->youtube_id ?? '',
            $video->title,
            $video->channel_name,
        );

        return array_merge($bundle, [
            'player_media_type' => 'youtube',
            'playback_via_youtube' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSpotify(
        string $type,
        string $spotifyId,
        string $title,
        ?string $playbackYoutubeId = null,
        ?string $artist = null,
    ): array {
        $externalUrl = $this->spotify->buildOpenUrl($type, $spotifyId);
        $youtubeId = $playbackYoutubeId;

        if (! $youtubeId && $this->spotifyYouTube->canResolveType($type)) {
            $youtubeId = $this->spotifyYouTube->resolve($title, $type, $artist);
        }

        if ($youtubeId) {
            $embed = $this->youTube->buildMusicPlaybackBundle($youtubeId, $title, 'Spotify');

            return array_merge($embed, [
                'external_url' => $externalUrl,
                'playback_youtube_id' => $youtubeId,
                'playback_via_youtube' => true,
                'player_media_type' => 'youtube',
                'playback_notice' => 'Diputar penuh via YouTube (padanan judul Spotify). Tanpa login Spotify.',
            ]);
        }

        return [
            'embed_url' => $this->spotify->buildEmbedUrl($type, $spotifyId),
            'embed_fallbacks' => [],
            'external_url' => $externalUrl,
            'playback_notice' => $this->spotify->playbackNoticeForType($type),
            'playback_youtube_id' => null,
            'playback_via_youtube' => false,
            'player_media_type' => 'spotify',
        ];
    }

    public function detectSource(string $input): string
    {
        $input = strtolower(trim($input));

        if (str_contains($input, 'spotify.com') || str_starts_with($input, 'spotify:')) {
            return 'spotify';
        }

        return 'youtube';
    }
}
