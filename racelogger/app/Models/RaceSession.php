<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceSession extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'name',
        'type',
        'session_order',
    ];

    public function calendarRace()
    {
        return $this->belongsTo(CalendarRace::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}

