<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsRule extends Model
{
    protected $fillable = [
        'points_system_id',
        'session_type',
        'position',
        'points',
    ];

    public function pointsSystem()
    {
        return $this->belongsTo(PointsSystem::class);
    }
}

