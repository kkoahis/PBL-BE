<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'hotel_id' => $this->hotel_id,
            'room_count' => $this->room_count,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'description' => $this->description,
            'is_payment' => $this->is_payment,
            'payment_type' => $this->payment_type,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'date_booking' => $this->date_booking,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'booking_detail' => $this->bookingDetail,
            'payment' => $this->payment,
            
            'rooms' => $this->bookingDetail->map(function ($bookingDetail) {
                return $bookingDetail->room;
            }),
        ];
    }
}
