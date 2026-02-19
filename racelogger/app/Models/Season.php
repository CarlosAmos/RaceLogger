<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'series_id',
        'year',
        'is_simulated',
    ];

    protected $casts = [
        'is_simulated' => 'boolean',
    ];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function calendarRaces()
    {
        return $this->hasMany(CalendarRace::class)
                    ->orderBy('round_number');
    }

    public function seasonTeamEntries()
    {
        return $this->hasMany(SeasonTeamEntry::class);
    }

    public function pointsSystem()
    {
        return $this->hasOne(PointsSystem::class);
    }

    public function classes()
    {
        return $this->hasMany(SeasonClass::class)
                    ->orderBy('display_order');
    }

    public function seasonEntries()
    {
        return $this->hasMany(SeasonEntry::class);
    }

    public function seasonClasses()
    {
        return $this->hasMany(SeasonClass::class);
    }
}

