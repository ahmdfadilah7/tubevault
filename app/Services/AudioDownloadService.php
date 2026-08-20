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
        $youtubeId = $this->audioStreams->resolveYoutubeId($video);

        if (! $youtubeId) {
            throw new RuntimeException('Konten ini tidak punya sumber YouTube untuk dikonversi.');
        }

        $tryLocal = in_array($driver, ['auto', 'local'], true) && $preferFormat === 'mp3';
        $tryRemote = in_array($driver, ['auto', 'cobalt', 'remote'], true);
        $localError = null;

        if ($tryLocal) {
            if (! $this->localConverter->isAvailable()) {
                $localError = 'ffmpeg/yt-dlp belum tersedia di server.';
                Log::info('Local MP3 converter unavailable', [
                    'video_id' => $video->id,
                    'youtube_id' => $youtubeId,
                ]);
            } else {
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
                    $localError = $e->getMessage();
                    Log::info('Local MP3 convert skipped/failed, trying remote', [
                        'video_id' => $video->id,
                        'youtube_id' => $youtubeId,
                        'message' => $localError,
                    ]);

                    if ($driver === 'local') {
                        throw $e;
                    }
                }
            }

            if ($driver === 'local') {
                throw new RuntimeException($localError ?: 'Konversi lokal gagal / tidak tersedia.');
            }
        }

        if (! $tryRemote) {
            throw new RuntimeException($localError ?: 'Konversi lokal gagal / tidak tersedia.');
        }

        $remote = $this->audioStreams->downloadForSavedVideo(
            $video,
            $preferFormat === 'best' ? 'best' : 'mp3'
        );

        if (! $remote) {
            $hint = $localError
                ? " Detail lokal: {$localError}"
                : ' Pastikan YT_DLP_PATH/FFMPEG_PATH benar di VPS (php artisan mp3:check), atau sediakan Cobalt instance sendiri + COBALT_API_KEY.';

            throw new RuntimeException(
                'Gagal menyiapkan audio untuk YouTube ID '.$youtubeId.'.'.$hint
            );
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
            'yt_dlp' => $this->localConverter->resolvedYtDlpPath() ?? config('youtube.mp3.yt_dlp_path', 'yt-dlp'),
            'ffmpeg' => $this->localConverter->resolvedFfmpegPath() ?? config('youtube.mp3.ffmpeg_path', 'ffmpeg'),
        ];
    }
}
