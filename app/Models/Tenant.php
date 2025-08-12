<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use TomatoPHP\FilamentLocations\Models\Location;

class Tenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'cr_number',
        'entity_number',
        'bank_name',
        'bank_holder_name',
        'iban',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->slug = Str::slug($model->name);
        });
    }

    public function locations()
    {
        return $this->morphMany(Location::class, 'model');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

}
