<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    //
    public function entryClass()
    {
        return $this->belongsTo(EntryClass::class);
    }
}
