<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Someone signing in through NREIMS for the first time confirms their post,
 * centre and appointment date before using the portal: seed grant eligibility
 * depends on them.
 */
class EnsureProfileComplete
{
    private const ALLOWED = ['profile.complete', 'profile.complete.nreims', 'profile.complete.details', 'profile.complete.store', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsProfileCompletion() && ! $request->routeIs(self::ALLOWED)) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
