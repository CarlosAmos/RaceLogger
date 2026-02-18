<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackLayout extends Model
{
    protected $fillable = [
        'track_id',
        'name',
        'length_km',
        'turn_count',
        'active_from',
        'active_to',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function calendarRaces()
    {
        return $this->hasMany(CalendarRace::class);
    }

    public function scopeActiveForYear($query, $year)
    {
        return $query
            ->where(function ($q) use ($year) {
                $q->whereNull('active_from')
                ->orWhere('active_from', '<=', $year);
            })
            ->where(function ($q) use ($year) {
                $q->whereNull('active_to')
                ->orWhere('active_to', '>=', $year);
            });
    }

}
