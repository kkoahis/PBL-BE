<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Room;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends BaseController
{
    // public function index()
    // {
    //     $booking = Booking::get();
    //     return $this->sendResponse(BookingResource::collection($booking), 'Booking retrieved successfully.');
    // }

    public function show($id)
    {
        $booking = Booking::find($id);

        $user = $booking->user;

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        return $this->sendResponse(new BookingResource($booking), 'Booking retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $user = Auth::user();

        $validator = Validator::make($input, [
            // 'user_id' => 'required|exists:users,id,deleted_at,NULL',
            'hotel_id' => 'required|exists:hotel,id,deleted_at,NULL',
            'room_count' => 'required | numeric',
            // 'status' => 'pending',
            'description',
            'is_payment' => 'required | numeric | between:0,1',
            'payment_type' => 'required',
            'date_in' => 'required | date',
            'date_out' => 'required | date',
            'room_id' => 'required | exists:room,id,deleted_at,NULL',
        ]);

        // dd($validator);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->hotel_id = $input['hotel_id'];

        if (is_null($booking->hotel_id)) {
            return $this->sendError('Hotel ID not found.');
        }

        $booking->room_count = $input['room_count'];
        // if room_count > lenght of room_id, return error
        if ($booking->room_count > count($input['room_id'])) {
            return $this->sendError('Room count must be less than or equal to the number of rooms you choose.');
        }

        $booking->total_amount = 0;
        // $booking->status = 0;
        $booking->description = $input['description'];
        $booking->is_payment = $input['is_payment'];
        $booking->payment_type = $input['payment_type'];
        $booking->date_in = $input['date_in'];
        $booking->date_out = $input['date_out'];
        // date_booking is auto now + 7 hours
        $booking->date_booking = date('Y-m-d H:i:s', strtotime('+7 hours'));
        // dd($booking->created_at);

        $checkIn = $input['date_in'];
        $checkOut = $input['date_out'];

        // kiểm tra số ngày ở lại phải lớn hơn 1 ngày và nhỏ hơn 7 ngày, tính theo giờ phút giây
        $dateIn = strtotime($checkIn);
        $dateOut = strtotime($checkOut);
        $diff = $dateOut - $dateIn;
        $days = floor($diff / (60 * 60 * 24));

        if ($days < 1 || $days > 7) {
            return $this->sendError('Booking date is not available. Date must be greater than 1 day and less than 7 days.');
        }

        DB::beginTransaction();

        try {
            $conflictingBooking = BookingDetail::where('room_id', $input['room_id'])
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('date_in', [$checkIn, $checkOut])
                        ->orWhereBetween('date_out', [$checkIn, $checkOut])
                        ->orWhere(function ($query) use ($checkIn, $checkOut) {
                            $query->where('date_in', '<=', $checkIn)
                                ->where('date_out', '>=', $checkOut);
                        });
                })
                ->exists();

            if ($conflictingBooking) {
                DB::rollBack();
                return $this->sendError('Booking date is not available. Please choose another date or check again check in and check out date.');
            } else {
                $booking->save();
                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Error when create booking.');
        }

        // if booking create success, create payment and booking detail
        if ($booking) {
            // get room id from input and check if room id is not that hotel
            if (($input['room_id'])) {
                // dd($input['room_id']);
                // check in array room id if room id is not that hotel
                foreach ($input['room_id'] as $roomID) {
                    $room = Room::find($roomID);
                    if ($room->category->hotel_id != $booking->hotel_id) {
                        return $this->sendError('RoomID not found.');
                    }
                }
            } else {
                return $this->sendError('RoomID not found.');
            }

            $totalAmount = 0;
            // if room_count = 2, get 2 room id from input and create booking detail
            $roomIDs = array_slice($input['room_id'], 0, $booking->room_count);

            foreach ($roomIDs as $roomID) {
                $booking->bookingDetail()->create([
                    'booking_id' => $booking->id,
                    'room_id' => $roomID,
                    'date_in' => $input['date_in'],
                    'date_out' => $input['date_out'],
                ]);

                // total amount = price * days * room count
                $booking->total_amount += Room::find($roomID)->category->price * $days;

                $totalAmount = $booking->total_amount;
            }

            $booking->total_amount = $totalAmount;
            
            $currentDate = Carbon::now()->format('Y-m-d');
            $bookingDetails = BookingDetail::whereDate('date_in', $currentDate)->get();

            foreach ($bookingDetails as $bookingDetail) {
                $roomId = $bookingDetail->room_id;
                $room = Room::find($roomId);

                // Cập nhật trạng thái phòng tại đây
                $room->status = 1;
                $room->save();
            }


            // if payment_type is cash, dont create payment
            if ($input['payment_type'] != 'cash') {
                $booking->payment()->create([
                    'booking_id' => $booking->id,
                    'payment_type' => $input['payment_type'],
                    'is_payment' => $input['is_payment'],
                    'total_amount' => $booking->total_amount,
                    'qr_code' => 'https://buy.stripe.com/test_dR6g1ucrc6zWf7ybIL',
                    'payment_status' => 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => [
                'booking' => $booking,
                'booking_detail' => $booking->bookingDetail()->get(),
                'payment' => $booking->payment()->get(),
                'category' => $booking->hotel()->first()->category()->first(),
                'room' => $booking->bookingDetail()->first()->room()->first(),
            ]
        ], 200);
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

        $user = Auth::user();

        // if role is hotel -> can view booking of that hotel
        if ($user->role != 'hotel') {
            return $this->sendError('You are not allowed to view booking of hotel.');
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return $this->sendResponse(BookingResource::collection($booking), 'Booking retrieved successfully.');
        }
    }

    public function getBookingByUserid($id)
    {
        $booking = Booking::where('user_id', $id)->with('payment', 'bookingDetail.room.category')->paginate(20);
        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        $user = Auth::user();

        if ($user->id != $id) {
            return $this->sendError('You can not get booking of another user.');
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $booking
            ], 200);
        }
    }
}
