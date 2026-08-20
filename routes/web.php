<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\PlaylistController as AdminPlaylistController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
| SEO: pastikan sitemap & robots selalu file statis, bukan SPA HTML
*/
Route::get('/robots.txt', function () {
    $path = public_path('robots.txt');

    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

/*
| Admin panel — URL: /my-panel
*/
Route::prefix('my-panel')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    });

    Route::post('logout', [AdminAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/toggle-admin', [AdminUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('videos', [AdminVideoController::class, 'index'])->name('videos.index');
        Route::delete('videos/{video}', [AdminVideoController::class, 'destroy'])->name('videos.destroy');

        Route::get('playlists', [AdminPlaylistController::class, 'index'])->name('playlists.index');
        Route::delete('playlists/{playlist}', [AdminPlaylistController::class, 'destroy'])->name('playlists.destroy');

        Route::get('feedback', [AdminFeedbackController::class, 'index'])->name('feedback.index');
        Route::get('feedback/{feedback}', [AdminFeedbackController::class, 'show'])->name('feedback.show');
        Route::delete('feedback/{feedback}', [AdminFeedbackController::class, 'destroy'])->name('feedback.destroy');

        Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
    });
});

/*
| SPA publik — meta/branding dari Site Settings
*/
Route::get('/{path?}', function (?string $path = null) {
    $settings = app(SiteSettingsService::class);

    if (File::isFile(public_path('index.html')) || File::isDirectory(public_path('assets'))) {
        return response()
            ->view('spa', [
                'settings' => $settings->all(),
                'logoUrl' => $settings->logoUrl(),
                'faviconUrl' => $settings->faviconUrl(),
                'ogImageUrl' => $settings->ogImageUrl(),
            ])
            ->header('Cache-Control', 'no-cache, private');
    }

    return view('welcome');
})->where('path', '.*');
