<?php

namespace App\Console\Commands;

use App\Services\LocalMp3ConverterService;
use Illuminate\Console\Command;

class Mp3CheckCommand extends Command
{
    protected $signature = 'mp3:check';

    protected $description = 'Cek kesiapan ffmpeg & yt-dlp untuk konversi MP3 lokal';

    public function handle(LocalMp3ConverterService $converter): int
    {
        $configuredYt = (string) config('youtube.mp3.yt_dlp_path', 'yt-dlp');
        $configuredFf = (string) config('youtube.mp3.ffmpeg_path', 'ffmpeg');
        $resolvedYt = $converter->resolvedYtDlpPath();
        $resolvedFf = $converter->resolvedFfmpegPath();

        $this->info('Driver: '.config('youtube.mp3.driver', 'auto'));
        $this->line('yt-dlp configured: '.$configuredYt);
        $this->line('yt-dlp resolved:   '.($resolvedYt ?: 'MISSING'));
        $this->line('ffmpeg configured: '.$configuredFf);
        $this->line('ffmpeg resolved:   '.($resolvedFf ?: 'MISSING'));
        $this->line('Service available: '.($converter->isAvailable() ? '<fg=green>YES</>' : '<fg=red>NO</>'));

        $dir = (string) config('youtube.mp3.temp_dir', storage_path('app/tmp/mp3'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->line('Temp dir: '.$dir.(is_writable($dir) ? ' (writable)' : ' (NOT writable)'));

        if (! $converter->isAvailable()) {
            $this->newLine();
            $this->warn('Tip VPS: set path absolut di .env, contoh:');
            $this->line('  YT_DLP_PATH=/usr/local/bin/yt-dlp');
            $this->line('  FFMPEG_PATH=/usr/bin/ffmpeg');
            $this->line('Lalu: php artisan config:clear && php artisan mp3:check');
        }

        return $converter->isAvailable() ? self::SUCCESS : self::FAILURE;
    }
}
