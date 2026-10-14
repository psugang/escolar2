<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $student_id_number
 * @property string|null $curp
 * @property Carbon|null $birth_date
 * @property string|null $gender
 * @property string $status
 * @property string $full_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property School|null $school
 * @property StudentEnrollment|null $currentEnrollment
 */
class Student extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'student_id_number',
        'curp',
        'birth_date',
        'gender',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * @return HasOne<StudentEnrollment, $this>
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)
            ->where('status', 'ACTIVO')
            ->whereNull('ends_on')
            ->latest('starts_on');
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    /**
     * @return HasMany<StudentGuardian, $this>
     */
    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    public function markAsBaja(): void
    {
        $this->update(['status' => 'BAJA']);

        $active = $this->currentEnrollment;
        if ($active) {
            $active->update([
                'status' => 'BAJA',
                'ends_on' => today(),
            ]);
        }
    }
}
