<?php

namespace App\Support;

final class AnonymousLabel
{
    public static function forUser(int $userId): string
    {
        $code = strtoupper(substr(hash('crc32b', 'tubevault-member-'.$userId), 0, 6));

        return "Anggota #{$code}";
    }
}
