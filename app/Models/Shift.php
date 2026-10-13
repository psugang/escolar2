<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
    ];

    public function schoolGroups(): HasMany
    {
        return $this->hasMany(SchoolGroup::class);
    }
}

