<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\RefreshToken;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Jwt;
use App\Support\JwtIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string', 'size:6'],
        ]);

        $user = User::where('email', $data['email'])->first();
        $log = fn (bool $ok, ?string $motif = null) => LoginLog::create([
            'user_id' => $user?->id,
            'email_tente' => $data['email'],
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'succes' => $ok,
            'motif_echec' => $motif,
            'date' => now(),
        ]);

        if (! $user || ! Hash::check($data['password'], $user->mot_de_passe_hash)) {
            if ($user) {
                $user->increment('echecs_connexion');
                if ($user->echecs_connexion >= 5) {
                    $user->update(['verrouille_jusqu_a' => now()->addMinutes(15)]);
                }
            }
            $log(false, 'identifiants_invalides');
            throw ValidationException::withMessages(['email' => ['Identifiants invalides.']]);
        }

        if ($user->isLocked()) {
            $log(false, 'compte_verrouille');
            return $this->error('ACCOUNT_LOCKED', 'Compte temporairement verrouille. Reessayez plus tard.', 423);
        }

        if ($user->statut !== 'actif') {
            $log(false, 'compte_inactif');
            return $this->error('ACCOUNT_DISABLED', 'Compte desactive. Contactez l\'administrateur.', 403);
        }

        if ($user->two_factor_actif) {
            if (empty($data['two_factor_code'])) {
                return $this->ok(['requires_2fa' => true]);
            }
            if (! $this->verifyTotp($user, $data['two_factor_code'])) {
                $log(false, '2fa_invalide');
                return $this->error('TWO_FACTOR_INVALID', 'Code de double authentification invalide.', 422);
            }
        }

        $user->update([
            'echecs_connexion' => 0,
            'verrouille_jusqu_a' => null,
            'dernier_login_at' => now(),
        ]);
        $user->load('roles.permissions');

        // Refresh token stocke (hash) pour revocation
        $tokens = JwtIssuer::issue($user);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokens['refresh_token']),
            'expire_le' => now()->addSeconds((int) env('JWT_REFRESH_TTL', 604800)),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $log(true);
        return $this->ok($this->profile($user) + $tokens);
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request)
    {
        $data = $request->validate(['refresh_token' => ['required', 'string']]);
        $claims = Jwt::decode($data['refresh_token']);
        if (! $claims || ($claims['type'] ?? '') !== 'refresh') {
            return $this->error('TOKEN_INVALID', 'Refresh token invalide.', 401);
        }

        $stored = RefreshToken::where('token_hash', hash('sha256', $data['refresh_token']))
            ->whereNull('revoque_le')->first();
        if (! $stored || $stored->expire_le->isPast()) {
            return $this->error('TOKEN_EXPIRED', 'Refresh token expire. Reconnectez-vous.', 401);
        }

        $user = User::with('roles.permissions')->find($claims['sub']);
        if (! $user || $user->statut !== 'actif') {
            return $this->error('ACCOUNT_DISABLED', 'Compte indisponible.', 403);
        }

        // Rotation : revoque l'ancien, emet une nouvelle paire
        $stored->update(['revoque_le' => now()]);
        $tokens = JwtIssuer::issue($user);
        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $tokens['refresh_token']),
            'expire_le' => now()->addSeconds((int) env('JWT_REFRESH_TTL', 604800)),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return $this->ok($this->profile($user) + $tokens);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            RefreshToken::where('token_hash', hash('sha256', $token))->update(['revoque_le' => now()]);
        }
        return $this->ok(['message' => 'Deconnecte avec succes.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request)
    {
        $claims = $request->attributes->get('jwt');
        $user = User::with('roles.permissions')->find($claims['sub'] ?? null);
        if (! $user) {
            return $this->error('NOT_FOUND', 'Utilisateur introuvable.', 404);
        }
        return $this->ok($this->profile($user));
    }

    /**
     * POST /api/v1/auth/2fa/setup - genere un secret TOTP
     */
    public function twoFactorSetup(Request $request)
    {
        $claims = $request->attributes->get('jwt');
        $user = User::findOrFail($claims['sub']);
        if (! $user->two_factor_secret) {
            $user->update(['two_factor_secret' => $this->base32Encode(random_bytes(20))]);
        }
        $otpauth = 'otpauth://totp/Patrimoine:'.$user->email.'?secret='.$user->two_factor_secret.'&issuer=Patrimoine';
        return $this->ok(['secret' => $user->two_factor_secret, 'otpauth_url' => $otpauth]);
    }

    /**
     * POST /api/v1/auth/2fa/enable - active apres verification d'un code
     */
    public function twoFactorEnable(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $claims = $request->attributes->get('jwt');
        $user = User::findOrFail($claims['sub']);
        if (! $this->verifyTotp($user, $data['code'])) {
            return $this->error('TWO_FACTOR_INVALID', 'Code invalide.', 422);
        }
        $user->update(['two_factor_actif' => true]);
        return $this->ok(['two_factor_actif' => true]);
    }

    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'structure_id' => $user->structure_id,
            'roles' => $user->roles->pluck('code'),
            'permissions' => $user->permissions(),
            'two_factor_actif' => (bool) $user->two_factor_actif,
            'must_change_password' => (bool) $user->must_change_password_at,
        ];
    }

    private function verifyTotp(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        $key = $this->base32Decode($user->two_factor_secret);
        $time = (int) floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $calc = substr(hash_hmac('sha1', pack('N*', 0, $time + $i), $key, true), -4);
            $calc = unpack('N', $calc)[1] & 0x7FFFFFFF;
            $calc = str_pad((string) ($calc % 1000000), 6, '0', STR_PAD_LEFT);
            if (hash_equals($calc, $code)) {
                return true;
            }
        }
        return false;
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    private function base32Decode(string $data): string
    {
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $bits = '';
        foreach (str_split(strtoupper(rtrim($data, '='))) as $c) {
            $bits .= str_pad(decbin($alphabet[$c] ?? 0), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
