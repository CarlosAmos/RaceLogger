<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonTeamEntry extends Model
{
    protected $fillable = [
        'season_id',
        'team_id',
        'engine_supplier_id',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function engineSupplier()
    {
        return $this->belongsTo(EngineSupplier::class);
    }

    public function carEntries()
    {
        return $this->hasMany(CarEntry::class);
    }
}

