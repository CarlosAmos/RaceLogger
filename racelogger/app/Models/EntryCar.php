<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryCar extends Model
{
    //
    protected $fillable = [
        'entry_class_id',
        'car_model_id',
        'car_number',
        'livery_name',
        'chassis_code',
    ];

    public function entryClass()
    {
        return $this->belongsTo(EntryClass::class);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function drivers()
    {
        return $this->belongsToMany(
            Driver::class,
            'entry_car_driver' // <-- explicitly define table
        )->withTimestamps();
    }
}
