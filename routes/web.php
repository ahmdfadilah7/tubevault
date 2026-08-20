<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
| SEO: pastikan sitemap & robots selalu file statis, bukan SPA HTML
| (aman jika request masuk lewat index.php tanpa rewrite file).
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
| SPA: jika index.html ada (production), layani TubeVault.
| Tanpa ini, Hostinger yang mengarahkan semua ke index.php tetap menampilkan welcome Laravel.
*/
Route::get('/{path?}', function (?string $path = null) {
    $index = public_path('index.html');

    if (File::isFile($index)) {
        return response()->file($index);
    }

    return view('welcome');
})->where('path', '.*');
