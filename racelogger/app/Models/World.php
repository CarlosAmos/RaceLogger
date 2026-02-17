<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class World extends Model
{
    protected $fillable = [
        'name',
        'start_year',
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
}

