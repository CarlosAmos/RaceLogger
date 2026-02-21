<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualifyingResult extends Model
{
    protected $fillable = [
        'qualifying_session_id',
        'entry_car_id',
        'position',
        'best_lap_time_ms',
        'average_lap_time_ms',
        'laps_set',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function session()
    {
        return $this->belongsTo(QualifyingSession::class, 'qualifying_session_id');
    }

    public function entryCar()
    {
        return $this->belongsTo(EntryCar::class);
    }
}