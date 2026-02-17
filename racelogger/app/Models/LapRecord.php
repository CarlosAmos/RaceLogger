<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LapRecord extends Model
{
    protected $fillable = [
        'world_id',
        'track_layout_id',
        'session_type',
        'driver_id',
        'season_id',
        'lap_time_ms',
        'record_date',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function trackLayout()
    {
        return $this->belongsTo(TrackLayout::class);
    }
}

