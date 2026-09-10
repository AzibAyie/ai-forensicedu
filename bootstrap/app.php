<?php
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
        // Railway (and most PaaS hosts) terminate TLS at their edge proxy and
        // forward plain HTTP internally. Without trusting that proxy's
        // X-Forwarded-Proto header, Laravel has no way to know the original
        // request was actually HTTPS — so every url()/route()/action() call
        // generates http:// links even on the live https:// site, which the
        // browser then silently blocks as mixed content on every fetch()
        // call (this is why saved answers never reached the server in
        // production while working fine locally over plain HTTP). Trusting
        // '*' is safe here since the container is only ever reached through
        // the platform's own proxy, never directly from the internet.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
