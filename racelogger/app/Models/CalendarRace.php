<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarRace extends Model
{
    protected $fillable = [
        'season_id',
        'track_layout_id',
        'round_number',
        'name',
        'race_date',
    ];

    protected $casts = [
        'race_date' => 'date',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function trackLayout()
    {
        return $this->belongsTo(TrackLayout::class);
    }

    public function raceSessions()
    {
        return $this->hasMany(RaceSession::class);
    }
}

