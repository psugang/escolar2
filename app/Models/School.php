<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class School extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
    ];

    public function schoolYears(): HasMany
    {
        return $this->hasMany(SchoolYear::class);
    }

    public function memberships(): HasMany
    {
    return $this->hasMany(SchoolMembership::class);
    }
}
