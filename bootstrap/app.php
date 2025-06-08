<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => CheckRole::class . ':Admin',
            'owner' => CheckRole::class . ':Owner',
            'gudang' => CheckRole::class . ':Gudang',
            'cs' => CheckRole::class . ':CS',
            'penitip' => CheckRole::class . ':Penitip',
            'pembeli' => CheckRole::class . ':Pembeli',
            'organisasi' => CheckRole::class . ':Organisasi',
            'kurir' => CheckRole::class . ':Kurir',
            'hunter' => CheckRole::class . ':Hunter',
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // $schedule->command('app:batalkan-penjualan-expired')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->sendOutputTo(storage_path('logs/batal.log'))
        //     ->emailOutputOnFailure('you@example.com'); // opsional, jika punya mail setup
        // $schedule->command('app:pengambilan-transaksi-expired')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->sendOutputTo(storage_path('logs/pengambilan-expired.log'));
        $schedule->command('app:notif-penitipan')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/notif-penitipan.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
