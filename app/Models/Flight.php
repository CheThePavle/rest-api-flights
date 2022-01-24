<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Flight extends Model
{
    use HasFactory;

    public function airportFrom()
    {
        return $this->hasOne(Airport::class, 'id', 'from_id');
    }

    public function airportTo()
    {
        return $this->hasOne(Airport::class, 'id', 'to_id');
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    // ???
    public function getTimeFromAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    public function getTimeToAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }
}
