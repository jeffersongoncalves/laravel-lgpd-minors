<?php

namespace JeffersonGoncalves\LgpdMinors\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JeffersonGoncalves\LgpdMinors\MinorsManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks accounts suspended under art. 24, §4. Let your appeal route through without it.
 */
class BlockSuspended
{
    public function __construct(protected MinorsManager $minors) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($this->minors->isSuspended($user instanceof Model ? $user : null)) {
            abort(403, 'This account is suspended. You can appeal the decision.');
        }

        return $next($request);
    }
}
