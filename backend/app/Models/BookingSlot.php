<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['booking_id', 'court_id', 'booking_date', 'slot_hour'];
}