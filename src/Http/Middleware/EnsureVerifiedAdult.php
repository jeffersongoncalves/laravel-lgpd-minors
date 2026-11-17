<?php

namespace JeffersonGoncalves\LgpdMinors\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JeffersonGoncalves\LgpdMinors\MinorsManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content, products or services improper for under-18s: reliable check on every access,
 * self-declaration does not count (Lei 15.211/2025, art. 9, §1).
 */
class EnsureVerifiedAdult
{
    public function __construct(protected MinorsManager $minors) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($this->minors->isVerifiedAdult($user instanceof Model ? $user : null)) {
            return $next($request);
        }

        $route = config('lgpd-minors.verify_route');

        if (is_string($route) && ! $request->expectsJson()) {
            return redirect()->guest(route($route));
        }

        abort(403, 'Age verification required.');
    }
}
