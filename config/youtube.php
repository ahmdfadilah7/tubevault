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

    // JWT / API key untuk Cobalt instance (api.cobalt.tools publik sekarang wajib auth)
    'cobalt_api_key' => env('COBALT_API_KEY'),

    'oembed_url' => 'https://www.youtube.com/oembed',

    /*
    | YouTube Data API v3 — untuk mencari padanan lagu Spotify→YouTube (paling andal).
    | Google Cloud → aktifkan "YouTube Data API v3" → API key (boleh sama project dengan OAuth).
    */
    'api_key' => env('YOUTUBE_API_KEY'),

    /*
    | Konversi unduhan MP3 (self-hosted).
    | driver: auto (lokal dulu, fallback Cobalt), local, cobalt
    */
    'mp3' => [
        'driver' => env('MP3_CONVERT_DRIVER', 'auto'),
        // Pakai path absolut di VPS, mis. /usr/local/bin/yt-dlp — PHP-FPM sering tidak punya PATH shell
        'yt_dlp_path' => env('YT_DLP_PATH', 'yt-dlp'),
        'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
        'audio_quality' => env('MP3_AUDIO_QUALITY', '5'), // 0=best, 5=hemat RAM/CPU (cocok VPS 1GB)
        'max_filesize' => env('MP3_MAX_FILESIZE', '40M'),
        'timeout' => (int) env('MP3_CONVERT_TIMEOUT', 300),
        'lock_seconds' => (int) env('MP3_CONVERT_LOCK', 360),
        'temp_dir' => env('MP3_TEMP_DIR', storage_path('app/tmp/mp3')),
        'temp_ttl_minutes' => (int) env('MP3_TEMP_TTL', 60),
        'player_clients' => env('MP3_YT_PLAYER_CLIENTS', 'android,web,mweb'),
    ],

    'music_keywords' => [
        'official audio', 'music video', 'lyrics', 'lyric video',
        'audio only', ' ost', 'soundtrack', 'feat.', 'ft. vevo',
        'vevo', 'remix', 'live performance',
    ],

];
