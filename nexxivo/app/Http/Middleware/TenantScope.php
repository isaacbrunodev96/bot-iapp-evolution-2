<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantScope
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $tenantId = Auth::user()->tenant_id;
            // Compartilha tenant_id para uso em controllers/models
            $request->merge(['tenant_id' => $tenantId]);
        }
        return $next($request);
    }
}
