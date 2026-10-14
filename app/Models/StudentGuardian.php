<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $student_id
 * @property string $guardian_id
 * @property string $relationship
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Student|null $student
 * @property Guardian|null $guardian
 */
class StudentGuardian extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'guardian_id',
        'relationship',
    ];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
