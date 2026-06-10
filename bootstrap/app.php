<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // <-- LÍNEA AÑADIDA PARA HABILITAR LA API
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1. Configuración de middleware web (quitamos la 'a' extraña)
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 🚀 2. EXCEPCIÓN DEL CSRF PARA n8n
        // Esto le da pase libre al robot para mandar los correos eliminados sin error 419
        $middleware->validateCsrfTokens(except: [
            'api/deleted-emails',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
