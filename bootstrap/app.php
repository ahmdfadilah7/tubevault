<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'optional.sanctum' => \App\Http\Middleware\OptionalSanctumAuth::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'site.maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('my-panel') || $request->is('my-panel/*')) {
                return '/my-panel/login';
            }

            return '/login';
        });

        $middleware->redirectUsersTo(function ($request) {
            if ($request->is('my-panel/login')) {
                return '/my-panel';
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
