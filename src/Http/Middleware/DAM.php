<?php

namespace Webkul\DAM\Http\Middleware;

use Closure;

class DAM
{
    /**
     * Handle an incoming request;
     */
    public function handle($request, Closure $next)
    {
        // abort_if(! core()->getConfigData(''), 404);

        return $next($request);
    }
}
