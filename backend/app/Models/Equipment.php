<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = ['name', 'price', 'stock', 'is_active'];

    protected $casts = ['price' => 'float', 'stock' => 'integer', 'is_active' => 'boolean'];
}