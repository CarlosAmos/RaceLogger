<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualifyingSession extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'name',
        'session_order',
        'race_number',
        'is_elimination',
        'final_target',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function qualifyingResults()
    {
        return $this->hasMany(QualifyingResult::class);
    }

    public function calendarRace()
    {
        return $this->belongsTo(CalendarRace::class);
    }

    public function results()
    {
        return $this->hasMany(QualifyingResult::class)
            ->orderBy('position');
    }
}