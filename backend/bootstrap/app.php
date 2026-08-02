<?php

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
        $middleware->alias([
            'admin.api' => \App\Http\Middleware\EnsureAdminApi::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API error contract: every /api/* error is JSON with the same shape
        // the controllers use — { success: false, message, errors? }. Without
        // this, a browser hitting a protected API URL got redirected to the
        // web login page, and unhandled errors leaked HTML error pages to the
        // SPA clients.
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Recurso no encontrado.'], 404);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            // Specific HTTP exceptions (403 aborts, 405, 429…) keep their
            // status via the JSON renderer above — only opaque server errors
            // collapse to a clean 500 without leaking internals.
            if (
                $request->is('api/*')
                && !config('app.debug')
                && !($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
            ) {
                \Illuminate\Support\Facades\Log::error('Unhandled API exception', [
                    'path' => $request->path(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
            }
        });
    })->create();
