<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'is_multiclass',
    ];

    protected $casts = [
        'is_multiclass' => 'boolean',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    public function calendarRaces()
    {
        return $this->hasMany(\App\Models\CalendarRace::class, 'series_id');
    }
}

