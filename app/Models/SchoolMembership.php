<?php

namespace App\Models;

use App\Enums\SchoolRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolMembership extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SchoolMembershipRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(SchoolMembershipRole::class);
    }

    public function hasRole(SchoolRole|string $role): bool
    {
        $roleValue = $role instanceof SchoolRole
            ? $role->value
            : $role;

        return $this->roles()
            ->where('role', $roleValue)
            ->exists();
    }
}
