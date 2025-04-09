<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TomatoPHP\FilamentLocations\Models\Location;

class Customer extends Model
{
protected $fillable= ['name', 'phone', 'email'];
public function locations()
{
    return $this->morphMany(Location::class, 'model');
}
}
