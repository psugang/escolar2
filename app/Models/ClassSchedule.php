<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_class_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property Carbon $effective_from
 * @property Carbon|null $effective_until
 * @property string $day_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property SchoolClass|null $schoolClass
 */
class ClassSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_class_id',
        'day_of_week',
        'start_time',
        'end_time',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function getDayNameAttribute(): string
    {
        return match ($this->day_of_week) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            default => 'Domingo',
        };
    }

    /**
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
