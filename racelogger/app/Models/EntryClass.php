<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryClass extends Model
{
    protected $fillable = [
        'race_class_id',
    ];
    //
    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function raceClass()
    {
        return $this->belongsTo(SeasonClass::class, 'race_class_id');
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function seasonEntry()
    {
        return $this->belongsTo(SeasonEntry::class);
    }

    public function entryCars()
    {
        return $this->hasMany(EntryCar::class);
    }


}
