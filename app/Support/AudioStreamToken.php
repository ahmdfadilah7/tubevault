<?php

namespace App\Support;

class AudioStreamToken
{
    /** @return array{expires: int, sig: string} */
    public static function issue(int $videoId, ?int $playlistId, int $ttlMinutes = 60): array
    {
        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $sig = self::sign($videoId, $playlistId, $expires);

        return ['expires' => $expires, 'sig' => $sig];
    }

    public static function verify(int $videoId, ?int $playlistId, int $expires, string $sig): bool
    {
        if ($expires < time()) {
            return false;
        }

        $expected = self::sign($videoId, $playlistId, $expires);

        return hash_equals($expected, $sig);
    }

    private static function sign(int $videoId, ?int $playlistId, int $expires): string
    {
        $payload = implode(':', [$videoId, $playlistId ?? 0, $expires]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
