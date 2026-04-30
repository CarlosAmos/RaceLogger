<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonClass extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'sub_class',
        'display_order',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function seasonClasses()
    {
        return $this->hasMany(SeasonClass::class);
    }

    public function entryClasses()
    {
        return $this->hasMany(EntryClass::class, 'race_class_id');
    }
}

