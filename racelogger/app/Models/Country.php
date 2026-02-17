<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'iso_code',
        'continent'
    ];

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }
}

