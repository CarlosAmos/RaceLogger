<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSystem extends Model
{
    protected $fillable = ['name', 'description'];

    public function rules()
    {
        return $this->hasMany(PointSystemRule::class);
    }

    public function bonusRules()
    {
        return $this->hasMany(PointSystemBonusRule::class);
    }
}
