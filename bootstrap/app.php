<?php

use App\Http\Middleware\SetLocale;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Outside the panels (private files, the CV), a guest signs in on the candidate login.
        $middleware->redirectGuestsTo(fn () => route('filament.student.auth.login'));

        $middleware->alias([
            'role'              => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'        => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'=> \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errors are logged as usual and, on the live site, emailed to
        // ALERT_EMAIL (see App\Support\ErrorAlert).
        $exceptions->report(fn (\Throwable $e) => \App\Support\ErrorAlert::send($e));
    })->create();
