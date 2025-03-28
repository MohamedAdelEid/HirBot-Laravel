<?php

namespace App\Shared\Middleware;

use App\Shared\Enums\UserRoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userRole = Auth::user()->role->value;

        // Convert string roles to Enum values for validation
        $allowedRoles = array_map(fn($role) => UserRoleEnum::tryFrom($role)?->value, $roles);

        if (!in_array($userRole, $allowedRoles, true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}

