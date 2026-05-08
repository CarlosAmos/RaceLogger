<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Constructor extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'country_id'
    ];
    //
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function carModels()
    {
        return $this->hasMany(CarModel::class);
    }

    public function entrants()
    {
        return $this->hasMany(Entrant::class);
    }

    public function manufacturedEngines()
    {
        return $this->hasMany(Engine::class);
    }
}
