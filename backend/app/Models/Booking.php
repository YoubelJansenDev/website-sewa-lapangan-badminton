<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'court_id', 'booking_date', 'start_time', 'duration_hours',
        'status', 'rejection_reason', 'total_price', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'booking_date' => 'date:Y-m-d',
        'total_price' => 'float',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingEquipment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}