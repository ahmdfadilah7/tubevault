<?php

namespace App\Services;

use App\Models\SavedVideo;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AudioDownloadService
{
    public function __construct(
        private readonly AudioStreamService $audioStreams,
        private readonly LocalMp3ConverterService $localConverter,
    ) {}

    /**
     * Prefer local ffmpeg/yt-dlp MP3, then Cobalt/remote fallback.
     *
     * @return array{
     *   type: 'file'|'url',
     *   path?: string,
     *   url?: string,
     *   mime_type: string,
     *   extension: string,
     *   format: string,
     *   source: string,
     *   youtube_id?: string,
     *   bytes?: int
     * }
     */
    public function prepare(SavedVideo $video, string $preferFormat = 'mp3'): array
    {
        $preferFormat = strtolower($preferFormat);
        $driver = strtolower((string) config('youtube.mp3.driver', 'auto'));

        $tryLocal = in_array($driver, ['auto', 'local'], true) && $preferFormat === 'mp3';
        $tryRemote = in_array($driver, ['auto', 'cobalt', 'remote'], true);

        if ($tryLocal && $this->localConverter->isAvailable()) {
            try {
                $local = $this->localConverter->convertSavedVideo($video, $this->audioStreams);

                return [
                    'type' => 'file',
                    'path' => $local['path'],
                    'mime_type' => $local['mime_type'],
                    'extension' => $local['extension'],
                    'format' => $local['format'],
                    'source' => $local['source'],
                    'youtube_id' => $local['youtube_id'],
                    'bytes' => $local['bytes'],
                ];
            } catch (RuntimeException $e) {
                Log::info('Local MP3 convert skipped/failed, trying remote', [
                    'video_id' => $video->id,
                    'message' => $e->getMessage(),
                ]);

                if ($driver === 'local') {
                    throw $e;
                }
            }
        }

        if (! $tryRemote && $driver === 'local') {
            throw new RuntimeException('Konversi lokal gagal / tidak tersedia.');
        }

        $remote = $this->audioStreams->downloadForSavedVideo(
            $video,
            $preferFormat === 'best' ? 'best' : 'mp3'
        );

        if (! $remote) {
            throw new RuntimeException('Gagal menyiapkan audio. Pastikan konten punya sumber YouTube.');
        }

        return [
            'type' => 'url',
            'url' => $remote['url'],
            'mime_type' => $remote['mime_type'],
            'extension' => $remote['extension'],
            'format' => $remote['format'],
            'source' => $remote['source'] ?? 'remote',
            'youtube_id' => $remote['youtube_id'] ?? null,
        ];
    }

    public function status(): array
    {
        return [
            'driver' => config('youtube.mp3.driver', 'auto'),
            'local_available' => $this->localConverter->isAvailable(),
            'yt_dlp' => config('youtube.mp3.yt_dlp_path', 'yt-dlp'),
            'ffmpeg' => config('youtube.mp3.ffmpeg_path', 'ffmpeg'),
        ];
    }
}
