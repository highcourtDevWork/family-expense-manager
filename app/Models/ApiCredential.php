<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret',
        'last_used_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * The secret is never exposed in array/JSON output.
     *
     * @var list<string>
     */
    protected $hidden = ['secret'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mint a new credential for a user. Returns the persisted model together
     * with the one-time plaintext secret — the secret is hashed before storage
     * and can never be retrieved again.
     *
     * @return array{0: self, 1: string}
     */
    public static function generate(User $user, string $name): array
    {
        $plainSecret = 'fem_sec_'.Str::random(48);

        $credential = $user->apiCredentials()->create([
            'name' => $name,
            'key' => 'fem_key_'.Str::random(32),
            'secret' => Hash::make($plainSecret),
        ]);

        return [$credential, $plainSecret];
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Verify a presented secret against the stored hash.
     */
    public function secretMatches(string $plainSecret): bool
    {
        return Hash::check($plainSecret, $this->secret);
    }
}
