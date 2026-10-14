<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $schoolParameter = $request->route('school');

        abort_unless($schoolParameter !== null, 404);

        $school = $schoolParameter instanceof School
            ? $schoolParameter
            : School::query()->whereKey((string) $schoolParameter)->firstOrFail();

        abort_unless($user->canAccessSchool($school), 404);

        return $next($request);
    }
}
