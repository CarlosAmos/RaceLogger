<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSystemRule extends Model
{
    protected $fillable = [
        'point_system_id',
        'type',
        'position',
        'points'
    ];

    public function pointSystem()
    {
        return $this->belongsTo(PointSystem::class);
    }
}
