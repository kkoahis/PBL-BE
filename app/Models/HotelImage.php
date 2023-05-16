<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Hotel;

class HotelImage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'hotel_image';

    protected $fillable = [
        'hotel_id',
        'image_url',
        'image_description',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
