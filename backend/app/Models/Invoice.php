<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['booking_id', 'invoice_number', 'amount', 'status', 'method', 'paid_at'];

    protected $casts = ['amount' => 'float', 'paid_at' => 'datetime'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}