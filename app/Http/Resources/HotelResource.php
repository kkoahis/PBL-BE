<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;

class HotelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // pagination

        return [
            'id' => $this->id,
            'created_by' => $this->created_by,
            'name' => $this->name,
            'price' => $this->price,
            'email' => $this->email,
            'description' => $this->description,
            'amenities' => $this->amenities,
            'Safety_Hygiene' => $this->Safety_Hygiene,
            'address' => $this->address,
            'city' => $this->city,
            'nation' => $this->nation,
            'hotline' => $this->hotline,
            'room_total' => $this->room_total,
            'parking_slot' => $this->parking_slot,
            'bathrooms' => $this->bathrooms,
            'rating' => $this->rating,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'guests' => $this->guests,
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d'),

            'images' => $this->hotelImage,
            'categories' => $this->category,
            // get all rooms of categories
            'rooms' => $this->category->map(function ($category) {
                return $category->room;
            })
        ];
    }
}
