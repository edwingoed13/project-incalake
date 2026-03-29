<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        // Check if user has the required permission
        if (!$this->userHasPermission($user, $permission)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has a specific permission based on their role
     */
    private function userHasPermission($user, string $permission): bool
    {
        $role = $user->role ?? 'customer';
        $permissions = config("permissions.roles.{$role}.permissions", []);

        return $permissions[$permission] ?? false;
    }
}
