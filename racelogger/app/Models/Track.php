<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    protected $fillable = [
        'name',
        'country',
    ];

    public function layouts()
    {
        return $this->hasMany(TrackLayout::class);
    }
}

