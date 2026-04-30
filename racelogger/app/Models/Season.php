<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'series_id',
        'year',
        'point_system_id',
        'replace_driver_id',
        'substitute_driver_id',
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

    public function pointSystem()
    {
        return $this->belongsTo(PointSystem::class);
    }

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    /** Driver being replaced in the substitution rule. */
    public function replaceDriver()
    {
        return $this->belongsTo(Driver::class, 'replace_driver_id');
    }

    /** Driver substituting in place of replaceDriver. */
    public function substituteDriver()
    {
        return $this->belongsTo(Driver::class, 'substitute_driver_id');
    }
}

