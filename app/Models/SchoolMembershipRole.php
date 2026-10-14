<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolMembershipRole extends Model
{
    protected $fillable = [
        'school_membership_id',
        'role',
    ];

    /**
     * @return BelongsTo<SchoolMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(SchoolMembership::class, 'school_membership_id');
    }
}
