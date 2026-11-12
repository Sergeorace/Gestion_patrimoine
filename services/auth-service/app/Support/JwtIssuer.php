<?php

namespace App\Support;

/**
 * Emission de JWT RS256 (auth-service uniquement - cle privee).
 */
class JwtIssuer
{
    public static function issue(User $user): array
    {
        $now = time();
        $accessTtl = (int) env('JWT_ACCESS_TTL', 900);
        $refreshTtl = (int) env('JWT_REFRESH_TTL', 604800);

        $roles = $user->roles->pluck('code')->all();

        $access = self::encode([
            'iss' => 'patrimoine-auth',
            'aud' => 'patrimoine-api',
            'iat' => $now,
            'exp' => $now + $accessTtl,
            'jti' => (string) \Illuminate\Support\Str::uuid(),
            'sub' => $user->id,
            'nom' => trim($user->prenom.' '.$user->nom),
            'email' => $user->email,
            'structure_id' => $user->structure_id,
            'roles' => $roles,
            'permissions' => $user->permissions(),
            'two_factor' => (bool) $user->two_factor_actif,
        ]);

        $refresh = self::encode([
            'iss' => 'patrimoine-auth',
            'aud' => 'patrimoine-api',
            'iat' => $now,
            'exp' => $now + $refreshTtl,
            'jti' => (string) \Illuminate\Support\Str::uuid(),
            'sub' => $user->id,
            'type' => 'refresh',
        ]);

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }

    private static function encode(array $payload): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $h = self::b64url(json_encode($header));
        $p = self::b64url(json_encode($payload));
        $signature = '';
        openssl_sign("$h.$p", $signature, self::privateKey(), OPENSSL_ALGO_SHA256);
        return "$h.$p.".self::b64url($signature, false);
    }

    private static function b64url(string $data, bool $encode = true): string
    {
        return $encode
            ? rtrim(strtr(base64_encode($data), '+/', '-_'), '=')
            : base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private static function privateKey()
    {
        $path = env('JWT_PRIVATE_KEY_FILE', '/etc/patrimoine/jwt_private.pem');
        if (is_file($path)) {
            return file_get_contents($path);
        }
        $inline = env('JWT_PRIVATE_KEY');
        return $inline ? (str_starts_with($inline, '-----') ? $inline : base64_decode($inline)) : null;
    }
}
