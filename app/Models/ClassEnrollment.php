<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_class_id
 * @property string $student_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property SchoolClass|null $schoolClass
 * @property Student|null $student
 */
class ClassEnrollment extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_class_id',
        'student_id',
    ];

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
