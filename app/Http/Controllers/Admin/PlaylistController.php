<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaylistController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $playlists = Playlist::query()
            ->with('user')
            ->withCount('items')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.playlists.index', compact('playlists', 'q'));
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        $name = $playlist->name;
        $playlist->items()->delete();
        $playlist->delete();

        return back()->with('success', "Playlist \"{$name}\" berhasil dihapus.");
    }
}
