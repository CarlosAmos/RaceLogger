<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EngineSupplier extends Model
{
    protected $fillable = [
        'name',
        'manufacturer',
    ];

    public function seasonEntries()
    {
        return $this->hasMany(SeasonTeamEntry::class);
    }
}

