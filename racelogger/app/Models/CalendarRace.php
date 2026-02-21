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

    public function results()
    {
        return $this->hasMany(Result::class);
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

    public function isComplete(): bool
    {
        return $this->results()->exists();
    }

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


}
