<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'race_session_id',
        'entry_car_id',
        'position',
        'class_position',
        'status',
        'laps_completed',
        'gap_to_leader_ms',
        'gap_laps_down',
        'fastest_lap_time_ms',
        'fastest_lap',
        'points_awarded',
    ];

    public function raceSession()
    {
        return $this->belongsTo(RaceSession::class);
    }

    protected $casts = [
        'fastest_lap' => 'boolean',
        'points_awarded' => 'decimal:2',
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

    public function entryCar()
    {
        return $this->belongsTo(EntryCar::class);
    }

    public function drivers()
    {
        return $this->hasMany(ResultDriver::class)
            ->orderBy('driver_order');
    }

    public function driverModels()
    {
        return $this->belongsToMany(
            Driver::class,
            'result_drivers'
        )->withPivot('driver_order')
        ->orderBy('pivot_driver_order');
    }

    public function resultDrivers()
    {
        return $this->hasMany(ResultDriver::class);
    }
}