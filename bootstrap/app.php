<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => CheckRole::class . ':Admin',
            'gudang' => CheckRole::class . ':Gudang',
            'cs' => CheckRole::class . ':CS',
            'penitip' => CheckRole::class . ':Penitip',
            'pembeli' => CheckRole::class . ':Pembeli',
            'organisasi' => CheckRole::class . ':Organisasi',
            'kurir' => CheckRole::class . ':Kurir',
            'hunter' => CheckRole::class . ':Hunter',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
