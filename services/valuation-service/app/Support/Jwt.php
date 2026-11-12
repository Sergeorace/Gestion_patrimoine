<?php

namespace App\Support;

/**
 * Verification des JWT RS256 emis par auth-service.
 * Cle publique partagee via JWT_PUBLIC_KEY_FILE (montage docker/keys).
 */
class Jwt
{
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$h, $p, $s] = $parts;

        $header = json_decode(self::b64urlDecode($h), true);
        $payload = json_decode(self::b64urlDecode($p), true);
        if (! is_array($header) || ! is_array($payload)) {
            return null;
        }
        if (($header['alg'] ?? '') !== 'RS256') {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        $signature = self::b64urlDecode($s);
        $publicKey = self::publicKey();
        if (! $publicKey) {
            return null;
        }
        $verified = openssl_verify("$h.$p", $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            return null;
        }

        return $payload;
    }

    public static function b64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    protected static function publicKey()
    {
        $path = env('JWT_PUBLIC_KEY_FILE', '/etc/patrimoine/jwt_public.pem');
        if (! is_file($path)) {
            // Secours : cle inline dans l'environnement
            $inline = env('JWT_PUBLIC_KEY');
            return $inline ? (str_starts_with($inline, '-----') ? $inline : base64_decode($inline)) : null;
        }
        return file_get_contents($path);
    }
}
