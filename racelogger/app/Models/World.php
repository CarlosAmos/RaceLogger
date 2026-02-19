<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class World extends Model
{
    protected $fillable = [
        'name',
        'start_year',
    ];

    protected $casts = [
        'is_canonical' => 'boolean',
        'current_year' => 'integer',
    ];


    public function series()
    {
        return $this->hasMany(Series::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function lapRecords()
    {
        return $this->hasMany(LapRecord::class);
    }

    public function constructors()
    {
        return $this->hasMany(Constructor::class);
    }

    public function entrants()
    {
        return $this->hasMany(Entrant::class);
    }

    public function engines()
    {
        return $this->hasMany(Engine::class);
    }
}
