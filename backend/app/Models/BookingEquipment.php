<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingEquipment extends Model
{
    protected $table = 'booking_equipment';

    public $timestamps = false;

    protected $fillable = ['booking_id', 'equipment_id', 'quantity', 'unit_price'];

    protected $casts = ['unit_price' => 'float'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}