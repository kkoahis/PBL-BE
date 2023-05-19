<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryImage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'category_image';

    protected $fillable = [
        'category_id',
        'image_url',
        'image_description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
