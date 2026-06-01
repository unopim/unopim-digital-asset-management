<?php

namespace Webkul\DAM\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Schema;
use Webkul\DAM\Models\DamConfiguration;

class DAM
{
    /**
     * Handle an incoming request;
     */
    public function handle($request, Closure $next)
    {
        if (Schema::hasTable('dam_configuration')) {
            DamConfiguration::all()->each(function ($row) {
                $path = DamConfiguration::KEY_MAP[$row->key] ?? null;
                if ($path) {
                    config([$path => filter_var($row->value, FILTER_VALIDATE_BOOLEAN)]);
                }
            });
        }

        return $next($request);
    }
}
