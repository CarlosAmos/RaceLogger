<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSystemBonusRule extends Model
{
    protected $fillable = [
        'point_system_id',
        'type',
        'points'
    ];

    public function pointSystem()
    {
        return $this->belongsTo(PointSystem::class);
    }
}
