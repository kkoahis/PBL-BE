<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'category';

    protected $fillable = [
        'hotel_id',
        'name',
        'description',

        'size',
        'bed',
        'bathroom_facilities',
        'amenities',
        'directions_view',
        'description',
        'price',
        'max_people',
        'is_smoking',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->hasMany(Room::class);
    }

    public function categoryImage()
    {
        return $this->hasMany(CategoryImage::class);
    }
}