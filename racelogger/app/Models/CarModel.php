<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    protected $fillable = [
        'constructor_id',
        'engine_id',
        'name',
        'year',
    ];

    //
    public function constructor()
    {
        return $this->belongsTo(Constructor::class);
    }

    public function engine()
    {
        return $this->belongsTo(Engine::class);
    }

    public function entryCars()
    {
        return $this->hasMany(EntryCar::class);
    }
}
