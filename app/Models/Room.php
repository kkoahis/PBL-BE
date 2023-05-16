<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stripe\FundingInstructions;

class Room extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'room';

    protected $fillable = [
        'hotel_id',
        'category_id',
        'name',
        'size',
        'bed',
        'bathroom_facilities',
        'amenities',
        'directions_view',
        'description',
        'status',
        'max_people',
        'price',
        'is_smoking',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function roomImage()
    {
        return $this->hasMany(RoomImage::class);
    }

    public function bookingDetail()
    {
        return $this->hasMany(BookingDetail::class);
    }
}
