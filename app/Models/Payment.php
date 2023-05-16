<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected $fillable = [
        'booking_id',
        'qr_code',
        'qr_code_url',
        'total_amount',
        'payment_status',
        'discount',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
