<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'owner_id'
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
}
