<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'race_session_id',
        'car_entry_id',
        'position',
        'grid_position',
        'laps_completed',
        'status',
        'lap_time_ms',
        'points_awarded',
    ];

    protected $casts = [
        'points_awarded' => 'float',
    ];

    public function raceSession()
    {
        return $this->belongsTo(RaceSession::class);
    }

    public function carEntry()
    {
        return $this->belongsTo(CarEntry::class);
    }
}

