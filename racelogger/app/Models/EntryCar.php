<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryCar extends Model
{
    //
    protected $fillable = [
        'season_entry_id',
        'entry_class_id',
        'car_model_id',
        'car_number',
        'livery_name',
        'chassis_code',
    ];

    public function seasonEntry()
    {
        return $this->belongsTo(SeasonEntry::class);
    }

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
        return $this->hasMany(Driver::class);
    }
}
