<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceSession extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'name',
        'session_order',
        'is_sprint',
        'reverse_grid',
        'reverse_grid_from_position',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function calendarRace()
    {
        return $this->belongsTo(CalendarRace::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}