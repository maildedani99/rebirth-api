<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserIsAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * Alias de middleware (reemplaza al antiguo $routeMiddleware de Kernel.php)
         * Ahora puedes usarlos en rutas como: ->middleware(['jwt.auth', 'verified', 'admin'])
         */
        $middleware->alias([
            'admin'     => EnsureUserIsAdmin::class,
            'verified'  => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'jwt.auth'  => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
            // Si necesitas “jwt.refresh”, puedes añadir:
            // 'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
        ]);

        /**
         * (Opcional) Personalizar grupos.
         * Laravel 11 ya monta los grupos por defecto, solo descomenta si quieres sobreescribir.
         */
        // $middleware->group('api', [
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        //     // \Illuminate\Http\Middleware\HandleCors::class, // si necesitas forzar CORS aquí
        // ]);
        //
        // $middleware->group('web', [
        //     \Illuminate\Cookie\Middleware\EncryptCookies::class,
        //     \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        //     \Illuminate\Session\Middleware\StartSession::class,
        //     \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        //     \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
