<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonClass extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'display_order',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}

