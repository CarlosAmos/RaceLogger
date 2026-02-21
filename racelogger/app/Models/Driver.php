<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'world_id',
        'first_name',
        'last_name',
        'country_id',
        'date_of_birth',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function lapRecords()
    {
        return $this->hasMany(LapRecord::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function entryCars()
    {
        return $this->belongsToMany(
            EntryCar::class,
            'entry_car_driver' // <-- explicitly define table
        )->withTimestamps();
    }

    public function resultAppearances()
    {
        return $this->hasMany(ResultDriver::class);
    }
}
