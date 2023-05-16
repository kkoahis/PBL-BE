<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'booking_id' => $this->booking_id,
            'title' => $this->title,
            'content' => $this->content,
            'rating' => $this->rating,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // get hotel id from booking id
            'hotel_id' => $this->booking->hotel_id,
        ];
    }
}
