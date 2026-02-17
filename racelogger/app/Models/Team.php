<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'base_country',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function seasonEntries()
    {
        return $this->hasMany(SeasonTeamEntry::class);
    }
}

