<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonEntry extends Model
{
    protected $fillable = [
        'entrant_id',
        'season_id',
        'series_id',
        'constructor_id',
        'display_name',
    ];

    //
    public function entrant()
    {
        return $this->belongsTo(Entrant::class);
    }

    public function entryClasses()
    {
        return $this->hasMany(EntryClass::class);
    }

    public function constructor()
    {
        return $this->belongsTo(Constructor::class);
    }



}
