<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ], $e->status);
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                if ($e instanceof HttpException) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'An error occurred.',
                    ], $e->getStatusCode());
                }

                return response()->json([
                    'message' => 'Server Error: ' . $e->getMessage(),
                ], 500);
            }
        });
    })->create();
