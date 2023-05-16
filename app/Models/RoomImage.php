<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomImage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'room_image';

    protected $fillable = [
        'room_id',
        'image_url',
        'image_description',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
