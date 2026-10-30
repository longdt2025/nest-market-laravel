<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\EnsureManager;
use App\Http\Middleware\SerializeDataWrites;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureAdmin::class, 'customer' => EnsureCustomer::class, 'manager' => EnsureManager::class]);
        $middleware->web(append: [EnsureAccountActive::class, SerializeDataWrites::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('shop.home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Default Laravel exception handling.
    })->create();
