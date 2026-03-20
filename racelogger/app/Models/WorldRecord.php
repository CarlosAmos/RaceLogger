<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldRecord extends Model
{
    protected $fillable = ['world_id', 'data', 'computed_at'];

    protected $casts = [
        'data'        => 'array',
        'computed_at' => 'datetime',
    ];

    public function world()
    {
        return $this->belongsTo(World::class);
    }
}
