<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Constructor extends Model
{
        protected $fillable = [
            'world_id',
            'name',
            'country_id'
        ];
    //
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

}
