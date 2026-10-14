<?php

namespace App\Http\Middleware;

use App\Enums\SchoolRole;
use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        abort_unless($user !== null, 401);

        if ($user->isGlobalAdmin()) {
            return $next($request);
        }

        $schoolParameter = $request->route('school');

        $school = $schoolParameter instanceof School
            ? $schoolParameter
            : School::query()->whereKey((string) $schoolParameter)->firstOrFail();

        abort_unless($user->canAccessSchool($school), 404);

        $allowedRoles = array_map(
            fn (string $role) => SchoolRole::from($role),
            $roles
        );

        foreach ($allowedRoles as $role) {
            if ($user->hasSchoolRole($school, $role)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
