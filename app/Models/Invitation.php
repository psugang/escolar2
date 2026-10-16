<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $school_id
 * @property string $inviter_id
 * @property string $email
 * @property string $role
 * @property string $first_name
 * @property string $last_name
 * @property string|null $employee_number
 * @property string|null $phone
 * @property string $token_hash
 * @property string $status
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read School $school
 * @property-read User $inviter
 */
class Invitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'inviter_id',
        'email',
        'role',
        'first_name',
        'last_name',
        'employee_number',
        'phone',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

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
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function isValid(): bool
    {
        return $this->status === 'PENDING' && $this->expires_at->isFuture();
    }

    /**
     * Generates a new invitation with a cryptographically secure token.
     * Only the token hash is persisted in the database.
     *
     * @param  array<string, mixed>  $data
     * @return array{invitation: self, plain_token: string}
     */
    public static function createInvitation(School $school, User $inviter, array $data, int $expiresInDays = 3): array
    {
        $plainToken = Str::random(48);
        $tokenHash = hash('sha256', $plainToken);

        // Invalidate previous pending invitations for this email in this school
        static::where('school_id', $school->id)
            ->where('email', strtolower(trim((string) $data['email'])))
            ->where('status', 'PENDING')
            ->update(['status' => 'REVOKED']);

        $invitation = static::create([
            'school_id' => $school->id,
            'inviter_id' => $inviter->id,
            'email' => strtolower(trim((string) $data['email'])),
            'role' => $data['role'] ?? 'PROFESOR',
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'employee_number' => isset($data['employee_number']) ? trim((string) $data['employee_number']) : null,
            'phone' => isset($data['phone']) ? trim((string) $data['phone']) : null,
            'token_hash' => $tokenHash,
            'status' => 'PENDING',
            'expires_at' => now()->addDays($expiresInDays),
        ]);

        return [
            'invitation' => $invitation,
            'plain_token' => $plainToken,
        ];
    }

    public static function findValidByToken(string $plainToken): ?self
    {
        $hash = hash('sha256', $plainToken);

        $invitation = static::where('token_hash', $hash)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return null;
        }

        return $invitation;
    }
}
