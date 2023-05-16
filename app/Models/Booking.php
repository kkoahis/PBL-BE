<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'booking';

    protected $fillable = [
        'user_id',
        'hotel_id',
        'room_count',
        'total_amount',
        'status',
        'description',
        'is_payment',
        'payment_type',
        'date_in',
        'date_out',
        'date_booking',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function bookingDetail()
    {
        return $this->hasMany(BookingDetail::class);
    }
}
