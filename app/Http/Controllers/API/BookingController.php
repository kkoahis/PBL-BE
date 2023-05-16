<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\Room;
use Exception;

class BookingController extends BaseController
{
    public function index()
    {
        $booking = Booking::get();
        return $this->sendResponse(BookingResource::collection($booking), 'Booking retrieved successfully.');
    }

    public function show($id)
    {
        $booking = Booking::find($id);

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        return $this->sendResponse(new BookingResource($booking), 'Booking retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        // dd($input);
        $validator = Validator::make($input, [
            'user_id' => 'required|exists:users,id,deleted_at,NULL',
            'hotel_id' => 'required|exists:hotel,id,deleted_at,NULL',
            'room_count' => 'required',
            'total_amount' => 'required',
            'status' => 'required',
            'description',
            'is_payment' => 'required',
            'payment_type' => 'required',
            'date_in' => 'required',
            'date_out' => 'required',
            'date_booking' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $booking = Booking::create($input);

        // if booking create success, create payment and booking detail
        if ($booking) {
            $booking->payment()->create([
                'booking_id' => $booking->id,
                'total_amount' => $input['total_amount'],
                'payment_status' => $input['is_payment'],
            ]);

            if (($input['room_id'])) {
                $roomID = Room::find($input['room_id']);
                if ($roomID) {
                    $booking->bookingDetail()->create([
                        'booking_id' => $booking->id,
                        'room_id' => $input['room_id'],
                    ]);
                } else {
                    return $this->sendError('RoomID not found.');
                }
            } else {
                return $this->sendError('RoomID not found.');
            }
        }

        return $this->sendResponse(new BookingResource($booking), 'Booking created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            // 'user_id' => 'required',
            // 'hotel_id' => 'required',
            'room_count',
            'total_amount',
            'status',
            'description',
            'is_payment',
            'payment_type',
            'date_in',
            'date_out',
            'date_booking',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $booking = Booking::find($id);
        if (is_null($booking)) {
            return $this->sendError('Booking ID not found.');
        }
        // $booking->user_id = $input['user_id'];
        // $booking->hotel_id = $input['hotel_id'];
        $booking->room_count = $input['room_count'];
        $booking->total_amount = $input['total_amount'];
        $booking->status = $input['status'];
        $booking->description = $input['description'];
        $booking->is_payment = $input['is_payment'];
        $booking->payment_type = $input['payment_type'];
        $booking->date_in = $input['date_in'];
        $booking->date_out = $input['date_out'];
        $booking->date_booking = $input['date_booking'];
        $booking->save();

        return $this->sendResponse(new BookingResource($booking), 'Booking updated successfully.');
    }

    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        // find payment with booking id is $id
        $payment = $booking->payment;

        $bookingDetail = $booking->bookingDetail;

        if ($booking->delete()) {
            if ($payment != null) {
                $payment->delete();
            }
            foreach ($bookingDetail as $key => $value) {
                $value->delete();
            }

            return $this->sendResponse([], 'Booking deleted successfully.');
        }
        return $this->sendError('Booking can not delete.');
    }


    public function getBookingByHotelId($id)
    {
        $booking = Booking::where('hotel_id', $id)->get();

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }
        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return $this->sendResponse(BookingResource::collection($booking), 'Booking retrieved successfully.');
        }
    }

    public function getBookingByUserid($id)
    {
        $booking = Booking::where('user_id', $id)->get();

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }
        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return $this->sendResponse(BookingResource::collection($booking), 'Booking retrieved successfully.');
        }
    }
}
