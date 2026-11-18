<?php

use App\Http\Middleware\Auth\CheckIfAdmin;
use App\Http\Middleware\Auth\CheckIfAdminOrSelf;
use App\Http\Middleware\Charity\CheckIfCharityAdmin;
use App\Http\Middleware\Charity\CheckIfGuest;
use App\Http\Middleware\Charity\CheckIfSessionMagic;
use App\Http\Middleware\Charity\CheckIfSuper;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust reverse proxies so HTTPS/cookies/CSRF behave correctly in production.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => CheckIfAdmin::class,
            'admin_or_self' => CheckIfAdminOrSelf::class,

            // Lief en Leed uses magic links instead of passwords
            'magic.valid' => CheckIfSessionMagic::class,
            'magic.admin' => CheckIfCharityAdmin::class,
            'magic.super' => CheckIfSuper::class,
            'magic.guest' => CheckIfGuest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
