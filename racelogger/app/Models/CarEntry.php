<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarEntry extends Model
{
    protected $fillable = [
        'season_team_entry_id',
        'car_model_name',
        'number',
    ];

    public function seasonTeamEntry()
    {
        return $this->belongsTo(SeasonTeamEntry::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
