<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPointsSystemOverride extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'points_system_id',
    ];

    public function calendarRace()
    {
        return $this->belongsTo(CalendarRace::class);
    }

    public function pointsSystem()
    {
        return $this->belongsTo(PointsSystem::class);
    }
}

