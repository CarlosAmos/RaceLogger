<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsSystem extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'fastest_lap_enabled',
        'fastest_lap_points',
        'fastest_lap_min_position',
        'pole_position_enabled',
        'pole_position_points',
        'quali_bonus_enabled',
    ];

    protected $casts = [
        'fastest_lap_enabled' => 'boolean',
        'pole_position_enabled' => 'boolean',
        'quali_bonus_enabled' => 'boolean',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function rules()
    {
        return $this->hasMany(PointsRule::class);
    }
}

