<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TomatoPHP\FilamentLocations\Models\Location;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email'];

    public function locations()
    {
        return $this->morphMany(Location::class, 'model');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
