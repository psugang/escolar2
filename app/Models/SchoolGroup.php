<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_year_id
 * @property string $shift_id
 * @property int $grade
 * @property string $section
 * @property string $name_with_shift
 * @property Shift|null $shift
 * @property SchoolYear|null $schoolYear
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SchoolGroup extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_year_id',
        'shift_id',
        'grade',
        'section',
    ];

    public function getNameWithShiftAttribute(): string
    {
        $shiftName = $this->shift ? $this->shift->name : '';

        return "{$this->grade}° '{$this->section}'".($shiftName ? " ({$shiftName})" : '');
    }

    /**
     * @return BelongsTo<SchoolYear, $this>
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
