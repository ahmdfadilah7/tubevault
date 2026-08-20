<?php

namespace App\Services;

use App\Models\SavedVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class LocalMp3ConverterService
{
    public function isAvailable(): bool
    {
        return $this->binaryWorks($this->ytDlpPath())
            && $this->binaryWorks($this->ffmpegPath(), ['-version']);
    }

    /**
     * Convert YouTube audio to a local MP3 file.
     *
     * @return array{path: string, mime_type: string, extension: string, format: string, source: string, youtube_id: string, bytes: int}
     */
    public function convertYoutubeId(string $youtubeId, ?string $title = null): array
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('ffmpeg/yt-dlp belum tersedia di server.');
        }

        $dir = $this->tempDir();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Gagal membuat folder sementara MP3.');
        }

        $lockPath = $dir.DIRECTORY_SEPARATOR.'.convert.lock';
        $lockHandle = fopen($lockPath, 'c+');
        if ($lockHandle === false) {
            throw new RuntimeException('Gagal membuat lock konversi.');
        }

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            throw new RuntimeException('Server sedang memproses unduhan lain. Coba lagi sebentar.');
        }

        try {
            return $this->runConversion($youtubeId, $title);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    public function convertSavedVideo(SavedVideo $video, AudioStreamService $audioStreams): array
    {
        $youtubeId = $audioStreams->resolveYoutubeId($video);
        if (! $youtubeId) {
            throw new RuntimeException('Konten ini tidak punya sumber YouTube untuk dikonversi.');
        }

        return $this->convertYoutubeId($youtubeId, $video->title);
    }

    /**
     * @return array{path: string, mime_type: string, extension: string, format: string, source: string, youtube_id: string, bytes: int}
     */
    private function runConversion(string $youtubeId, ?string $title): array
    {
        $dir = $this->tempDir();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Gagal membuat folder sementara MP3.');
        }

        $this->cleanupOldTempFiles($dir);

        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $youtubeId) ?: Str::random(8);
        $outTemplate = $dir.DIRECTORY_SEPARATOR.$safeId.'-%(id)s.%(ext)s';
        $watchUrl = "https://www.youtube.com/watch?v={$youtubeId}";

        $quality = (string) config('youtube.mp3.audio_quality', '5');
        $maxSize = (string) config('youtube.mp3.max_filesize', '40M');
        $timeout = (int) config('youtube.mp3.timeout', 300);

        $result = Process::timeout($timeout)
            ->env([
                'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            ])
            ->run([
                $this->ytDlpPath(),
                '--no-playlist',
                '--no-warnings',
                '--newline',
                '-x',
                '--audio-format', 'mp3',
                '--audio-quality', $quality,
                '--ffmpeg-location', dirname($this->ffmpegPath()) ?: '/usr/bin',
                '--max-filesize', $maxSize,
                '-o', $outTemplate,
                '--print', 'after_move:filepath',
                $watchUrl,
            ]);

        if (! $result->successful()) {
            Log::warning('Local MP3 conversion failed', [
                'youtube_id' => $youtubeId,
                'error' => Str::limit($result->errorOutput() ?: $result->output(), 500),
            ]);

            throw new RuntimeException('Konversi MP3 gagal. Pastikan video tersedia dan coba lagi.');
        }

        $printed = trim($result->output());
        $path = $this->resolveOutputPath($printed, $dir, $safeId);

        if (! $path || ! is_file($path)) {
            throw new RuntimeException('File MP3 tidak ditemukan setelah konversi.');
        }

        // Normalize extension to .mp3 if yt-dlp left another name
        if (! str_ends_with(strtolower($path), '.mp3')) {
            $mp3Path = preg_replace('/\.[^.]+$/', '.mp3', $path) ?: ($path.'.mp3');
            if ($mp3Path !== $path) {
                @rename($path, $mp3Path);
                $path = is_file($mp3Path) ? $mp3Path : $path;
            }
        }

        clearstatcache(true, $path);
        $bytes = (int) filesize($path);

        if ($bytes < 1024) {
            @unlink($path);
            throw new RuntimeException('Hasil konversi terlalu kecil / tidak valid.');
        }

        return [
            'path' => $path,
            'mime_type' => 'audio/mpeg',
            'extension' => 'mp3',
            'format' => 'mp3',
            'source' => 'local-ffmpeg',
            'youtube_id' => $youtubeId,
            'bytes' => $bytes,
            'title' => $title,
        ];
    }

    private function resolveOutputPath(string $printed, string $dir, string $safeId): ?string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $printed) ?: [])));
        foreach (array_reverse($lines) as $line) {
            if (is_file($line)) {
                return $line;
            }
            // Sometimes printed with quotes
            $unquoted = trim($line, " \t\"'");
            if (is_file($unquoted)) {
                return $unquoted;
            }
        }

        $matches = glob($dir.DIRECTORY_SEPARATOR.$safeId.'-*.mp3') ?: [];
        if ($matches === []) {
            $matches = glob($dir.DIRECTORY_SEPARATOR.$safeId.'-*.*') ?: [];
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $matches[0];
    }

    private function cleanupOldTempFiles(string $dir): void
    {
        $ttl = (int) config('youtube.mp3.temp_ttl_minutes', 60);
        $cutoff = time() - ($ttl * 60);

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function tempDir(): string
    {
        return (string) config('youtube.mp3.temp_dir', storage_path('app/tmp/mp3'));
    }

    private function ytDlpPath(): string
    {
        return (string) config('youtube.mp3.yt_dlp_path', 'yt-dlp');
    }

    private function ffmpegPath(): string
    {
        return (string) config('youtube.mp3.ffmpeg_path', 'ffmpeg');
    }

    /**
     * @param  list<string>  $args
     */
    private function binaryWorks(string $binary, array $args = ['--version']): bool
    {
        try {
            $result = Process::timeout(15)->run(array_merge([$binary], $args));

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
