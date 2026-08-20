<?php

namespace App\Console\Commands;

use App\Services\LocalMp3ConverterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class Mp3CheckCommand extends Command
{
    protected $signature = 'mp3:check';

    protected $description = 'Cek kesiapan ffmpeg & yt-dlp untuk konversi MP3 lokal';

    public function handle(LocalMp3ConverterService $converter): int
    {
        $yt = (string) config('youtube.mp3.yt_dlp_path', 'yt-dlp');
        $ff = (string) config('youtube.mp3.ffmpeg_path', 'ffmpeg');

        $this->info('Driver: '.config('youtube.mp3.driver', 'auto'));
        $this->line('yt-dlp path: '.$yt);
        $this->line('ffmpeg path: '.$ff);

        $ytOk = $this->probe($yt, ['--version']);
        $ffOk = $this->probe($ff, ['-version']);

        $this->line('yt-dlp: '.($ytOk ? '<fg=green>OK</>' : '<fg=red>MISSING</>'));
        $this->line('ffmpeg: '.($ffOk ? '<fg=green>OK</>' : '<fg=red>MISSING</>'));
        $this->line('Service available: '.($converter->isAvailable() ? '<fg=green>YES</>' : '<fg=red>NO</>'));

        $dir = (string) config('youtube.mp3.temp_dir', storage_path('app/tmp/mp3'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->line('Temp dir: '.$dir.(is_writable($dir) ? ' (writable)' : ' (NOT writable)'));

        return $converter->isAvailable() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<string>  $args
     */
    private function probe(string $bin, array $args): bool
    {
        try {
            $result = Process::timeout(15)->run(array_merge([$bin], $args));
            if ($result->successful()) {
                $this->line('  → '.trim(strtok($result->output() ?: $result->errorOutput(), "\n")));
            }

            return $result->successful();
        } catch (\Throwable $e) {
            $this->warn('  → '.$e->getMessage());

            return false;
        }
    }
}
