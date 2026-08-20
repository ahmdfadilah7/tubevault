<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderPlaylistItemsRequest;
use App\Http\Requests\StorePlaylistItemRequest;
use App\Http\Requests\StorePlaylistRequest;
use App\Http\Requests\UpdatePlaylistRequest;
use App\Http\Resources\PlaylistItemResource;
use App\Http\Resources\PlaylistResource;
use App\Services\PlaylistService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlists,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $list = $this->playlists->listForUser($request->user());

        return PlaylistResource::collection($list)->response();
    }

    public function store(StorePlaylistRequest $request): JsonResponse
    {
        $playlist = $this->playlists->create($request->user(), $request->validated());

        return (new PlaylistResource($playlist))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $playlist): PlaylistResource
    {
        $model = $this->playlists->findAccessible($request->user(), $playlist);

        return new PlaylistResource($model);
    }

    public function update(UpdatePlaylistRequest $request, int $playlist): PlaylistResource
    {
        $model = $this->playlists->findForUser($request->user(), $playlist);
        $updated = $this->playlists->update($model, $request->validated());

        return new PlaylistResource($updated);
    }

    public function destroy(Request $request, int $playlist): JsonResponse
    {
        $model = $this->playlists->findForUser($request->user(), $playlist);
        $this->playlists->delete($model);

        return response()->json(null, 204);
    }

    public function storeItem(StorePlaylistItemRequest $request, int $playlist): JsonResponse
    {
        $model = $this->playlists->findForUser($request->user(), $playlist);

        try {
            $item = $this->playlists->addItem($request->user(), $model, $request->validated());
            $item->load('savedVideo');
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $model->touch();

        return (new PlaylistItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function destroyItem(Request $request, int $playlist, int $item): JsonResponse
    {
        $model = $this->playlists->findForUser($request->user(), $playlist);
        $this->playlists->removeItem($model, $item);
        $model->touch();

        return response()->json(null, 204);
    }

    public function reorder(ReorderPlaylistItemsRequest $request, int $playlist): PlaylistResource
    {
        $model = $this->playlists->findForUser($request->user(), $playlist);
        $updated = $this->playlists->reorder($model, $request->validated('item_ids'));

        return new PlaylistResource($updated);
    }
}
