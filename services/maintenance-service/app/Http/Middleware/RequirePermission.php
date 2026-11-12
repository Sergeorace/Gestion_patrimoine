<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controle RBAC : la permission demandee doit figurer dans le claim JWT
 * "permissions" (ou "*" pour administrateur).
 * Usage : ->middleware('permission:bien.creer')
 */
class RequirePermission
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $claims = $request->attributes->get('jwt', []);
        $userPerms = $claims['permissions'] ?? [];

        $granted = in_array('*', $userPerms, true)
            || count(array_intersect($permissions, $userPerms)) > 0;

        if (! $granted) {
            return $this->error('FORBIDDEN', 'Permission insuffisante pour cette action.', 403);
        }

        return $next($request);
    }
}
