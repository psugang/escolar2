<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $class_session_id
 * @property string $student_id
 * @property string $status
 * @property string|null $recorded_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ClassSession|null $classSession
 * @property Student|null $student
 * @property User|null $recordedBy
 * @property AttendanceJustification|null $justification
 */
class Attendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_session_id',
        'student_id',
        'status',
        'recorded_by_user_id',
    ];

    /**
     * @return BelongsTo<ClassSession, $this>
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * @return HasMany<AttendanceLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * @return HasOne<AttendanceJustification, $this>
     */
    public function justification(): HasOne
    {
        return $this->hasOne(AttendanceJustification::class);
    }

    public function updateStatusWithLog(string $newStatus, User $user, ?string $reason = null): void
    {
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $this->update(['status' => $newStatus]);

        AttendanceLog::create([
            'attendance_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $user->id,
            'reason' => $reason,
        ]);
    }
}
