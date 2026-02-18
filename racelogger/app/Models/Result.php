<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'car_id',
        'driver_id',
        'position',
        'class_position',
        'time',
        'laps_completed',
        'status',
        'points',
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

    public function calendarRace()
    {
        return $this->belongsTo(CalendarRace::class);
    }
}

