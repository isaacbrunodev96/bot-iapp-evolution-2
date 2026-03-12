<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Prioridade: Se o usuário estiver autenticado, use o tenant_id dele
        if (auth()->check() && !auth()->user()->is_super_admin) {
            $tenantId = auth()->user()->tenant_id;
            session(['tenant_id' => $tenantId]);
            $request->merge(['tenant_id' => $tenantId]);
            return $next($request);
        }

        // 2. Para requisições de API webhook: Checar cabeçalho X-Tenant-ID
        if ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = escapeshellcmd($request->header('X-Tenant-ID'));
            session(['tenant_id' => $tenantId]);
            $request->merge(['tenant_id' => $tenantId]);
            return $next($request);
        }

        // 3. Fallback: Checar no body da requisição
        if ($request->has('tenant_id')) {
             session(['tenant_id' => $request->get('tenant_id')]);
             return $next($request);
        }

        return $next($request);
    }
}
