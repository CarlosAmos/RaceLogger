<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LapRecordLog extends Model
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
}

