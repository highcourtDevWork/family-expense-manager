<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\FamilyRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * All family membership rows for this user.
     */
    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * API credentials (key + hashed secret) issued to this user.
     */
    public function apiCredentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    /**
     * Families this user belongs to, with role/relationship pivot data.
     */
    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_members')
            ->withPivot(['role', 'relationship'])
            ->withTimestamps();
    }

    /**
     * The user's active family. For now this is simply their first membership;
     * multi-family switching can layer a "current_family_id" on top later.
     */
    public function currentFamily(): ?Family
    {
        return $this->families()->orderBy('family_members.id')->first();
    }

    /**
     * Whether this user is an admin of the given family.
     */
    public function isAdminOf(Family $family): bool
    {
        return $this->familyMemberships()
            ->where('family_id', $family->id)
            ->where('role', FamilyRole::Admin)
            ->exists();
    }
}
