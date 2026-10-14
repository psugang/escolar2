<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_class_id
 * @property Carbon $date
 * @property string $start_time
 * @property string $end_time
 * @property string $original_teacher_id
 * @property string|null $substitute_teacher_id
 * @property string $status
 * @property string|null $cancellation_reason
 * @property Teacher $effective_teacher
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property SchoolClass|null $schoolClass
 * @property Teacher|null $originalTeacher
 * @property Teacher|null $substituteTeacher
 */
class ClassSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_class_id',
        'date',
        'start_time',
        'end_time',
        'original_teacher_id',
        'substitute_teacher_id',
        'status',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function getEffectiveTeacherAttribute(): Teacher
    {
        return $this->substituteTeacher ?? $this->originalTeacher;
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function originalTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'original_teacher_id');
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assignSubstitute(Teacher $substitute): void
    {
        $this->update([
            'substitute_teacher_id' => $substitute->id,
        ]);
    }

    public function cancelSession(string $reason): void
    {
        $this->update([
            'status' => 'CANCELADA',
            'cancellation_reason' => $reason,
        ]);
    }
}
