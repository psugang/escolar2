<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $teacher_id
 * @property string $school_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Teacher|null $teacher
 * @property School|null $school
 */
class TeacherSchool extends Model
{
    use HasUuids;

    protected $fillable = [
        'teacher_id',
        'school_id',
    ];

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
