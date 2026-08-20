<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\AnonymousMemberResource;
use App\Models\User;
use App\Services\DiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function __construct(
        private readonly DiscoveryService $discovery,
    ) {}

    public function users(Request $request): JsonResponse
    {
        $users = $this->discovery->listUsers($request->user());

        return AnonymousMemberResource::collection($users)->response();
    }

    public function playlists(Request $request): JsonResponse
    {
        $playlists = $this->discovery->listRecommendedPlaylists($request->user());

        return PlaylistResource::collection($playlists)->response();
    }

    public function userPlaylists(Request $request, int $user): JsonResponse
    {
        User::query()->findOrFail($user);

        $playlists = $this->discovery->listPlaylistsForUser($request->user(), $user);

        return PlaylistResource::collection($playlists)->response();
    }
}
