<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engine extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'configuration',
        'capacity',
        'hybrid',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function carModels()
    {
        return $this->hasMany(CarModel::class);
    }
}