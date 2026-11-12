<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use HasUuids;

    public $timestamps = true;
    protected $table = 'refresh_tokens';

    protected $fillable = ['user_id', 'token_hash', 'expire_le', 'revoque_le', 'ip', 'user_agent'];

    protected $casts = ['expire_le' => 'datetime', 'revoque_le' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
