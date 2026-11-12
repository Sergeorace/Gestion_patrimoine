<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe_hash', 'structure_id',
        'statut', 'two_factor_actif', 'two_factor_secret', 'two_factor_backup',
        'echecs_connexion', 'verrouille_jusqu_a', 'dernier_login_at',
        'must_change_password_at',
    ];

    protected $hidden = ['mot_de_passe_hash', 'two_factor_secret', 'two_factor_backup'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withPivot('accorde_le');
    }

    public function permissions(): array
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($r) => $r->permissions)
            ->pluck('code')
            ->unique()
            ->values()
            ->all();
    }

    public function isLocked(): bool
    {
        return $this->verrouille_jusqu_a && $this->verrouille_jusqu_a->isFuture();
    }
}
