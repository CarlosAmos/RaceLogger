<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrant extends Model
{
    protected $fillable = [
        'name',
        'country_id',
    ];
    //
    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function seasonEntries()
    {
        return $this->hasMany(SeasonEntry::class);
    }
}
