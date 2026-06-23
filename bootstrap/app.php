<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
            channels: __DIR__.'/../routes/channels.php',

        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->expectsJson() && app()->isProduction()) {
                return response()->json(['message' => 'Error interno del servidor'], 500);
            }
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        // El contenedor del cron es efimero (sin volumen), por lo que el output
        // se manda a /dev/stdout para que quede capturado en los logs de Railway
        // en vez de perderse en un archivo que desaparece al terminar el contenedor.
        $schedule->command('reservas:en-curso')
            ->everyMinute()
            ->appendOutputTo('/dev/stdout');
        $schedule->command('reservas:recordatorios')
            ->everyMinute()
            ->appendOutputTo('/dev/stdout');
    })
    ->create();
