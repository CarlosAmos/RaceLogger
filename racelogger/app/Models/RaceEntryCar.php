<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceEntryCar extends Model
{
    protected $fillable = [
        'calendar_race_id',
        'entry_car_id',
    ];

    public function race()
    {
        return $this->belongsTo(CalendarRace::class, 'calendar_race_id');
    }

    public function entryCar()
    {
        return $this->belongsTo(EntryCar::class);
    }
}
