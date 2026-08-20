<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
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

    public function destroy(SavedVideo $video): RedirectResponse
    {
        $title = $video->title ?: 'Media #'.$video->id;
        $video->playlistItems()->delete();
        $video->delete();

        return back()->with('success', "\"{$title}\" berhasil dihapus.");
    }
}
