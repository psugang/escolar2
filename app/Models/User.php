<?php

namespace App\Models;

use App\Enums\SchoolRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $global_role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

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
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return HasMany<SchoolMembership, $this>
     */
    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function isGlobalAdmin(): bool
    {
        return $this->global_role === 'ADMIN_GLOBAL';
    }

    public function canAccessSchool(School $school): bool
    {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        return $this->schoolMemberships()
            ->where('school_id', $school->getKey())
            ->exists();
    }

    public function hasSchoolRole(
        School $school,
        SchoolRole|string $role
    ): bool {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        $roleValue = $role instanceof SchoolRole
            ? $role->value
            : $role;

        return $this->schoolMemberships()
            ->where('school_id', $school->getKey())
            ->whereHas('roles', function ($query) use ($roleValue) {
                $query->where('role', $roleValue);
            })
            ->exists();
    }
}
