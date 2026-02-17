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
}
