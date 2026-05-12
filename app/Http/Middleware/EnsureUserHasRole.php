<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Used like this in routes:
//   Route::middleware(['auth:sanctum', 'role:rider'])->group(...);
//   Route::middleware(['auth:sanctum', 'role:restaurant_owner,admin'])->group(...);
//
// The comma list means "allow ANY of these roles".
// This is what prevents a customer from calling rider-only endpoints
// (e.g. updating GPS) — a security requirement implied by the description.
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden. Required role: '.implode(' or ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
