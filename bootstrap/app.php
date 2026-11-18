<?php

use App\Http\Controllers\Generations\ReceiveWebhookController;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // The provider's webhook sits outside the web group: no session, no CSRF token (ADR 0010).
        then: function (): void {
            // One route per provider, by name; an unknown name gets 404 (ADR 0044).
            // SubstituteBindings turns {provider} into the enum; it brings no session and no CSRF.
            Route::post('webhooks/{provider}', ReceiveWebhookController::class)
                ->middleware(SubstituteBindings::class)
                ->name('webhooks.receive');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // A signed-in user opening a guest page, such as the home page, lands on Generate.
        $middleware->redirectUsersTo('/generate');

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
