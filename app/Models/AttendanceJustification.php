<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $attendance_id
 * @property string|null $document_path
 * @property string|null $document_name
 * @property string $reason
 * @property string $registered_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Attendance|null $attendance
 * @property User|null $registeredBy
 */
class AttendanceJustification extends Model
{
    use HasUuids;

    protected $fillable = [
        'attendance_id',
        'document_path',
        'document_name',
        'reason',
        'registered_by_user_id',
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
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
