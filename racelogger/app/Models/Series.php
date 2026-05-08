<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'name',
        'is_multiclass',
        'game',
    ];

    protected $casts = [
        'is_multiclass' => 'boolean',
    ];

    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    public function calendarRaces()
    {
        return $this->hasMany(\App\Models\CalendarRace::class, 'series_id');
    }
}

