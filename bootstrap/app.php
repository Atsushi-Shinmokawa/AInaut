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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // AppServiceExceptionのハンドリング
        // 例外がResponsableインターフェースを実装している場合、
        // Laravelが自動的にtoResponse()メソッドを呼び出す
        $exceptions->renderable(function (\App\Exceptions\AppServiceException $e, $request) {
            return $e->toResponse($request);
        });
    })->create();
