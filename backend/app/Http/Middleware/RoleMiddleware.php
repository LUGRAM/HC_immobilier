<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


// ============================================
// MIDDLEWARE: app/Http/Middleware/RoleMiddleware.php
// ============================================

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {

        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        if ($request->user()->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        return $next($request);
    }
}
