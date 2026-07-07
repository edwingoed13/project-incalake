<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON admin gate for the API surface. AdminMiddleware (web) redirects to a
 * login route that doesn't exist for API consumers, so admin API groups use
 * this instead: 401 without a user, 403 unless the user's role column grants
 * panel access (admin|staff via User::canAccessAdminPanel()).
 */
class EnsureAdminApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        if (!$user->canAccessAdminPanel()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores.',
            ], 403);
        }

        return $next($request);
    }
}
