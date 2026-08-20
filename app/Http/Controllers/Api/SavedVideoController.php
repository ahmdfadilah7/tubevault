<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewVideoRequest;
use App\Http\Requests\StoreSavedVideoRequest;
use App\Http\Requests\UpdateSavedVideoRequest;
use App\Http\Resources\SavedVideoResource;
use App\Services\AudioStreamService;
use App\Services\SavedVideoService;
use App\Services\YouTubeSearchService;
use App\Support\AudioStreamToken;
use App\Models\SavedVideo;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SavedVideoController extends Controller
{
    public function __construct(
        private readonly SavedVideoService $videos,
        private readonly AudioStreamService $audioStreams,
        private readonly YouTubeSearchService $youtubeSearch,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->videos->paginate(
            $request->user(),
            $request->string('search')->toString() ?: null,
            $request->integer('per_page', 12),
        );

        return SavedVideoResource::collection($paginator)->response();
    }

    public function show(Request $request, int $video): SavedVideoResource
    {
        $playlistId = $request->integer('playlist_id') ?: null;
        $model = $this->videos->findForUserOrPlaylist($request->user(), $video, $playlistId);

        return new SavedVideoResource($model);
    }

    public function store(StoreSavedVideoRequest $request): JsonResponse
    {
        try {
            $video = $this->videos->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return (new SavedVideoResource($video))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateSavedVideoRequest $request, int $video): SavedVideoResource
    {
        $model = $this->videos->findForUser($request->user(), $video);
        $updated = $this->videos->update($request->user(), $model, $request->validated());

        return new SavedVideoResource($updated);
    }

    public function destroy(Request $request, int $video): JsonResponse
    {
        $model = $this->videos->findForUser($request->user(), $video);
        $this->videos->delete($request->user(), $model);

        return response()->json(null, 204);
    }

    public function watch(Request $request, int $video): SavedVideoResource
    {
        $model = $this->videos->findForUser($request->user(), $video);
        $updated = $this->videos->recordWatch($request->user(), $model);

        return new SavedVideoResource($updated);
    }

    public function preview(PreviewVideoRequest $request): JsonResponse
    {
        try {
            $data = $this->videos->preview($request->user(), $request->validated('url'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function youtubeSearch(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());
        if (mb_strlen($q) < 3) {
            return response()->json(['data' => []]);
        }

        $results = $this->youtubeSearch->searchSuggestions(
            $q,
            $request->integer('limit', 5),
        );

        return response()->json(['data' => $results]);
    }

    public function audioStream(Request $request, int $video): JsonResponse
    {
        $playlistId = $request->integer('playlist_id') ?: null;
        $model = $this->videos->findForUserOrPlaylist($request->user(), $video, $playlistId);
        $stream = $this->audioStreams->forSavedVideo($model);

        if (! $stream) {
            return response()->json([
                'message' => 'Stream audio tidak tersedia untuk konten ini.',
            ], 404);
        }

        $token = AudioStreamToken::issue($video, $playlistId);
        $playUrl = url("/api/v1/videos/{$video}/audio-stream/play").'?'.http_build_query([
            'expires' => $token['expires'],
            'sig' => $token['sig'],
            'playlist_id' => $playlistId ?? '',
        ]);

        return response()->json([
            'data' => [
                'url' => $playUrl,
                'mime_type' => $stream['mime_type'],
                'youtube_id' => $stream['youtube_id'],
            ],
        ]);
    }

    public function audioStreamPlay(Request $request, int $video): StreamedResponse
    {
        $expires = $request->integer('expires');
        $sig = $request->string('sig')->toString();
        $playlistId = $request->integer('playlist_id') ?: null;

        if (! AudioStreamToken::verify($video, $playlistId, $expires, $sig)) {
            abort(403, 'Link stream tidak valid atau kedaluwarsa.');
        }

        $model = SavedVideo::query()->findOrFail($video);
        $upstream = $this->audioStreams->forSavedVideo($model);

        if (! $upstream) {
            abort(404, 'Stream audio tidak tersedia.');
        }

        $headers = [];
        if ($request->header('Range')) {
            $headers['Range'] = $request->header('Range');
        }

        $upstreamResponse = Http::timeout(90)
            ->withHeaders($headers)
            ->withOptions(['stream' => true])
            ->get($upstream['url']);

        if (! $upstreamResponse->successful()) {
            abort(502, 'Gagal mengambil stream audio.');
        }

        $responseHeaders = array_filter([
            'Content-Type' => $upstreamResponse->header('Content-Type') ?? $upstream['mime_type'],
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $upstreamResponse->header('Content-Length'),
            'Content-Range' => $upstreamResponse->header('Content-Range'),
            'Cache-Control' => 'no-store, private',
        ]);

        $status = $upstreamResponse->status();

        return response()->stream(function () use ($upstreamResponse) {
            $body = $upstreamResponse->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(16384);
                if (connection_aborted()) {
                    break;
                }
                flush();
            }
        }, $status, $responseHeaders);
    }

    public function downloadAudio(Request $request, int $video): StreamedResponse|JsonResponse
    {
        $playlistId = $request->integer('playlist_id') ?: null;
        $format = strtolower($request->string('format', 'mp3')->toString());
        if (! in_array($format, ['mp3', 'best'], true)) {
            $format = 'mp3';
        }

        $model = $this->videos->findForUserOrPlaylist($request->user(), $video, $playlistId);
        $stream = $this->audioStreams->downloadForSavedVideo($model, $format === 'best' ? 'best' : 'mp3');

        if (! $stream) {
            return response()->json([
                'message' => 'Gagal menyiapkan audio. Coba lagi nanti atau pastikan konten memiliki sumber YouTube.',
            ], 404);
        }

        $upstreamResponse = Http::timeout(180)
            ->withOptions(['stream' => true])
            ->get($stream['url']);

        if (! $upstreamResponse->successful()) {
            return response()->json([
                'message' => 'Gagal mengunduh audio dari sumber. Silakan coba lagi.',
            ], 502);
        }

        $extension = $stream['extension'] ?? 'mp3';
        $mime = $upstreamResponse->header('Content-Type') ?: ($stream['mime_type'] ?? 'audio/mpeg');
        $filename = $this->safeDownloadFilename($model->title ?: 'tubevault-audio', $extension);

        $responseHeaders = array_filter([
            'Content-Type' => $mime,
            'Content-Length' => $upstreamResponse->header('Content-Length'),
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'X-TubeVault-Audio-Format' => $stream['format'] ?? $extension,
            'X-TubeVault-Audio-Source' => $stream['source'] ?? 'unknown',
        ]);

        return response()->stream(function () use ($upstreamResponse) {
            $body = $upstreamResponse->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(16384);
                if (connection_aborted()) {
                    break;
                }
                flush();
            }
        }, 200, $responseHeaders);
    }

    private function safeDownloadFilename(string $title, string $extension): string
    {
        $base = Str::slug(Str::limit($title, 80, ''));
        if ($base === '') {
            $base = 'tubevault-audio';
        }

        return $base.'.'.ltrim($extension, '.');
    }
}
