<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Unauthorized access');
        }

        $rbacRoles = collect($roles)
            ->flatMap(fn (string $role) => match ($role) {
                'admin' => ['admin', 'super_admin'],
                'staff' => ['staff', 'storekeeper'],
                'manager' => ['manager'],
                default => [$role],
            })
            ->unique()
            ->all();

        if ($user->hasRole($rbacRoles) || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }
}
