<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'world_id',
        'first_name',
        'last_name',
        'nationality',
        'date_of_birth',
        'rating',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function lapRecords()
    {
        return $this->hasMany(LapRecord::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

