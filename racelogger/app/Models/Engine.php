<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engine extends Model
{
    protected $fillable = [
        'world_id',
        'constructor_id',
        'name',
        'configuration',
        'capacity',
        'hybrid',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function manufacturer()
    {
        return $this->belongsTo(Constructor::class, 'constructor_id');
    }

    public function carModels()
    {
        return $this->hasMany(CarModel::class);
    }
}