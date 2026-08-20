<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiscoveryController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\SavedVideoController;
use App\Http\Controllers\Api\SiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('site-settings', SiteSettingsController::class);

    Route::get('videos/preview', [SavedVideoController::class, 'preview'])
        ->middleware('optional.sanctum');
    Route::get('youtube/search', [SavedVideoController::class, 'youtubeSearch'])
        ->middleware(['optional.sanctum', 'throttle:15,1']);

    Route::get('videos/{video}/audio-stream/play', [SavedVideoController::class, 'audioStreamPlay']);

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::get('feedback', [FeedbackController::class, 'index']);
    Route::post('feedback', [FeedbackController::class, 'store'])
        ->middleware('optional.sanctum');
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::post('videos/{video}/watch', [SavedVideoController::class, 'watch']);
        Route::get('videos/{video}/audio-stream', [SavedVideoController::class, 'audioStream']);
        Route::get('videos/{video}/download-audio', [SavedVideoController::class, 'downloadAudio'])
            ->middleware('throttle:10,1');
        Route::apiResource('videos', SavedVideoController::class);

        Route::get('discover/users', [DiscoveryController::class, 'users']);
        Route::get('discover/playlists', [DiscoveryController::class, 'playlists']);
        Route::get('discover/users/{user}/playlists', [DiscoveryController::class, 'userPlaylists']);

        Route::get('playlists', [PlaylistController::class, 'index']);
        Route::post('playlists', [PlaylistController::class, 'store']);
        Route::get('playlists/{playlist}', [PlaylistController::class, 'show']);
        Route::put('playlists/{playlist}', [PlaylistController::class, 'update']);
        Route::delete('playlists/{playlist}', [PlaylistController::class, 'destroy']);

        Route::post('playlists/{playlist}/items', [PlaylistController::class, 'storeItem']);
        Route::delete('playlists/{playlist}/items/{item}', [PlaylistController::class, 'destroyItem']);
        Route::put('playlists/{playlist}/reorder', [PlaylistController::class, 'reorder']);
    });
});
