<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $attendance_id
 * @property string $old_status
 * @property string $new_status
 * @property string $changed_by_user_id
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Attendance|null $attendance
 * @property User|null $changedBy
 */
class AttendanceLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'attendance_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'reason',
    ];

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
