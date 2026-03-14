<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarRace extends Model
{
    protected $fillable = [
        'season_id',
        'track_layout_id',
        'round_number',
        'gp_name',
        'race_code',
        'race_date',
        'point_system_id',
        'sprint_race'
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function layout()
    {
        return $this->belongsTo(TrackLayout::class, 'track_layout_id');
    }

    public function pointSystem()
    {
        return $this->belongsTo(PointSystem::class);
    }


    public function qualifyingSessions()
    {
        return $this->hasMany(QualifyingSession::class);
    }

    /*
|--------------------------------------------------------------------------
| Lifecycle Helpers
|--------------------------------------------------------------------------
*/



    public function hasQualifying(): bool
    {
        return $this->qualifyingSessions()->exists();
    }

    public function canBeLocked(): bool
    {
        // Race must at least have results
        return $this->isComplete();
    }

    public function lock(): void
    {
        if (!$this->canBeLocked()) {
            throw new \Exception('Race cannot be locked until results exist.');
        }

        $this->update(['is_locked' => true]);
    }

    public function unlock(): void
    {
        $this->update(['is_locked' => false]);
    }

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function raceEntryCars()
    {
        return $this->hasMany(RaceEntryCar::class);
    }

    public function entryCars()
    {
        return $this->belongsToMany(
            EntryCar::class,
            'race_entry_cars'
        );
    }

    public function raceSessions()
    {
        return $this->hasMany(RaceSession::class)
            ->orderBy('session_order');
    }

    public function results()
    {
        return $this->hasManyThrough(
            \App\Models\Result::class,
            \App\Models\RaceSession::class,
            'calendar_race_id', // Foreign key on race_sessions
            'race_session_id',  // Foreign key on results
            'id',               // Local key on calendar_races
            'id'                // Local key on race_sessions
        );
    }

    public function trackLayout()
    {
        return $this->belongsTo(\App\Models\TrackLayout::class, 'track_layout_id');
    }
    
}
