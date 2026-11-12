<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\Jwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentification JWT sur les routes protegees.
 * Injecte les claims dans la requete : jwt.sub, jwt.permissions, jwt.structure_id.
 */
class VerifyJwt
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return $this->error('UNAUTHENTICATED', 'Jeton d\'accès manquant.', 401);
        }

        $claims = Jwt::decode($token);
        if (! $claims || empty($claims['sub'])) {
            return $this->error('TOKEN_INVALID', 'Jeton invalide ou expire.', 401);
        }

        $request->attributes->set('jwt', $claims);
        return $next($request);
    }
}
