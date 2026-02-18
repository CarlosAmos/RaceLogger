<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarRace extends Model
{
    protected $fillable = [
        'season_id',
        'track_layout_id',
        'round_number',
        'gp_name',
        'race_code',
        'race_date',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function layout()
    {
        return $this->belongsTo(TrackLayout::class, 'track_layout_id');
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    
}
