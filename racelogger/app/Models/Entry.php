<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    //
    public function constructor()
    {
        return $this->belongsTo(Constructor::class);
    }

    public function classes()
    {
        return $this->hasMany(EntryClass::class);
    }
}
