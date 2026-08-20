<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedVideo;
use App\Services\AudioStreamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoController extends Controller
{
    public function __construct(private AudioStreamService $audioStreams) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        $videos = SavedVideo::query()
            ->with('user')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('channel_name', 'like', "%{$q}%")
                        ->orWhere('youtube_id', 'like', "%{$q}%")
                        ->orWhere('spotify_id', 'like', "%{$q}%");
                });
            })
            ->when(in_array($type, ['youtube', 'spotify'], true), fn ($query) => $query->where('media_type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.videos.index', compact('videos', 'q', 'type'));
    }

    public function downloadMp3(SavedVideo $video): StreamedResponse|RedirectResponse
    {
        $stream = $this->audioStreams->downloadForSavedVideo($video, 'mp3');

        if (! $stream) {
            return back()->with('error', 'Gagal menyiapkan MP3 untuk media ini.');
        }

        $upstreamResponse = Http::timeout(180)
            ->withOptions(['stream' => true])
            ->get($stream['url']);

        if (! $upstreamResponse->successful()) {
            return back()->with('error', 'Gagal mengunduh audio dari sumber.');
        }

        $extension = $stream['extension'] ?? 'mp3';
        $mime = $upstreamResponse->header('Content-Type') ?: ($stream['mime_type'] ?? 'audio/mpeg');
        $base = Str::slug(Str::limit($video->title ?: 'tubevault-audio', 80, ''));
        if ($base === '') {
            $base = 'tubevault-audio';
        }
        $filename = $base.'.'.$extension;

        return response()->stream(function () use ($upstreamResponse) {
            $body = $upstreamResponse->toPsrResponse()->getBody();
            while (! $body->eof()) {
                echo $body->read(16384);
                if (connection_aborted()) {
                    break;
                }
                flush();
            }
        }, 200, array_filter([
            'Content-Type' => $mime,
            'Content-Length' => $upstreamResponse->header('Content-Length'),
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]));
    }

    public function destroy(SavedVideo $video): RedirectResponse
    {
        $title = $video->title ?: 'Media #'.$video->id;
        $video->playlistItems()->delete();
        $video->delete();

        return back()->with('success', "\"{$title}\" berhasil dihapus.");
    }
}
