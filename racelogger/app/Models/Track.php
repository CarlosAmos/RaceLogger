<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    protected $fillable = [
        'name',
        'country_id',
        'city',
    ];

    public function layouts()
    {
        return $this->hasMany(TrackLayout::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}

