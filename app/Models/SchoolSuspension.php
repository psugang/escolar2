<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $school_id
 * @property string $school_year_id
 * @property Carbon $date
 * @property string $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property School|null $school
 * @property SchoolYear|null $schoolYear
 */
class SchoolSuspension extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'school_year_id',
        'date',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
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
}
