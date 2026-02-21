<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultDriver extends Model
{
    protected $fillable = [
        'result_id',
        'driver_id',
        'driver_order',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function result()
    {
        return $this->belongsTo(Result::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}