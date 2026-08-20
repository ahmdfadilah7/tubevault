<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

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
