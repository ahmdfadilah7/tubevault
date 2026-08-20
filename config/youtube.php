<?php

return [

    /*
    | Pemutar utama selalu youtube-nocookie (kompatibel iframe).
    | Piped/Invidious hanya sebagai fallback opsional.
    */
    'player_mode' => env('YOUTUBE_PLAYER_MODE', 'embed'),

    'music_player_mode' => env('YOUTUBE_MUSIC_PLAYER_MODE', 'embed'),

    'use_listen_mode_for_music' => env('YOUTUBE_MUSIC_LISTEN_MODE', true),

    'piped_as_fallback' => env('YOUTUBE_PIPED_AS_FALLBACK', false),

    'allow_invidious_embed' => env('YOUTUBE_ALLOW_INVIDIOUS_EMBED', false),

    'invidious_base_url' => rtrim(env('INVIDIOUS_BASE_URL', 'https://invidious.f5.si'), '/'),

    'piped_base_url' => rtrim(env('PIPED_BASE_URL', 'https://piped.video'), '/'),

    'piped_instances' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'PIPED_INSTANCES',
            'https://pipedapi.kavin.rocks,https://pipedapi.adminforge.de,https://api.piped.projectsegfau.lt'
        ))
    ))),

    /*
    | Fallback audio stream untuk mode background (selain Piped).
    | Urutan: piped → invidious → cobalt
    */
    'audio_stream_sources' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('AUDIO_STREAM_SOURCES', 'piped,invidious,cobalt'))
    ))),

    'invidious_instances' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'INVIDIOUS_INSTANCES',
            'https://inv.nadeko.net,https://invidious.nerdvpn.de,https://yt.artemislena.eu,https://invidious.f5.si'
        ))
    ))),

    'cobalt_instances' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'COBALT_INSTANCES',
            'https://api.cobalt.tools,https://co.wuk.sh'
        ))
    ))),

    'oembed_url' => 'https://www.youtube.com/oembed',

    /*
    | YouTube Data API v3 — untuk mencari padanan lagu Spotify→YouTube (paling andal).
    | Google Cloud → aktifkan "YouTube Data API v3" → API key (boleh sama project dengan OAuth).
    */
    'api_key' => env('YOUTUBE_API_KEY'),

    'music_keywords' => [
        'official audio', 'music video', 'lyrics', 'lyric video',
        'audio only', ' ost', 'soundtrack', 'feat.', 'ft. vevo',
        'vevo', 'remix', 'live performance',
    ],

];
