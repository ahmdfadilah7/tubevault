<?php

return [

    'oembed_url' => 'https://open.spotify.com/oembed',

    'embed_base_url' => 'https://open.spotify.com/embed',

    /*
    | Cari padanan di YouTube (via Piped search) agar trek/episode Spotify
    | bisa diputar penuh tanpa login Spotify. Metadata tetap dari Spotify.
    */
    'playback_via_youtube' => env('SPOTIFY_PLAYBACK_VIA_YOUTUBE', true),

    'youtube_resolve_types' => ['track', 'episode'],

    /*
    | false = embed YouTube nocookie (disarankan, tanpa login Piped).
    | true = coba Piped sebagai fallback setelah YouTube.
    */
    'prefer_piped_for_playback' => env('SPOTIFY_PREFER_PIPED', false),

    'piped_search_instances' => array_filter(array_map(
        'trim',
        explode(',', env(
            'SPOTIFY_PIPED_SEARCH_INSTANCES',
            'https://pipedapi.kavin.rocks,https://pipedapi.adminforge.de,https://api.piped.yt'
        ))
    )),
];
