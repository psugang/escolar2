<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_id
 * @property string $school_year_id
 * @property string $subject_id
 * @property string $teacher_id
 * @property string|null $school_group_id
 * @property bool $is_workshop
 * @property string|null $name
 * @property string $display_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property School|null $school
 * @property SchoolYear|null $schoolYear
 * @property Subject|null $subject
 * @property Teacher|null $teacher
 * @property SchoolGroup|null $schoolGroup
 */
class SchoolClass extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'school_year_id',
        'subject_id',
        'teacher_id',
        'school_group_id',
        'is_workshop',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_workshop' => 'boolean',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->is_workshop && $this->name) {
            return $this->name;
        }

        $subjectName = $this->subject ? $this->subject->name : 'Materia';
        $groupName = $this->schoolGroup ? "{$this->schoolGroup->grade}°{$this->schoolGroup->section}" : '';

        return trim("{$subjectName} {$groupName}");
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<SchoolYear, $this>
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<SchoolGroup, $this>
     */
    public function schoolGroup(): BelongsTo
    {
        return $this->belongsTo(SchoolGroup::class);
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function workshopStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_enrollments')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /**
     * @return HasMany<ClassSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
