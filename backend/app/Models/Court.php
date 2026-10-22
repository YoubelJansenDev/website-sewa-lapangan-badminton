<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    protected $fillable = ['name', 'floor_type', 'price_per_hour', 'is_active'];

    protected $casts = ['price_per_hour' => 'float', 'is_active' => 'boolean'];
}