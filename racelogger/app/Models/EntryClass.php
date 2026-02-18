<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryClass extends Model
{
    //
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function raceClass()
    {
        return $this->belongsTo(RaceClass::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
