<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Hotel;
use App\Models\Reply;
use App\Models\Room;
use App\Models\User;
use App\Notifications\userReceiveBooking;
use App\Notifications\userRejectedBooking;
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

    // public function show($id)
    // {
    //     $booking = Booking::find($id);

    //     $user = $booking->user;

    //     if (is_null($booking)) {
    //         return $this->sendError('Booking not found.');
    //     }

    //     return $this->sendResponse(new BookingResource($booking), 'Booking retrieved successfully.');
    // }

    public function store(Request $request)
    {
        $user = Auth::user();
        // if role hotel or admin, return error
        if ($user->role == 'hotel' || $user->role == 'admin') {
            return $this->sendError('You do not have permission to create booking.');
        }

        $input = $request->all();

        $validator = Validator::make($input, [
            'hotel_id' => 'required|exists:hotel,id,deleted_at,NULL',
            'room_count' => 'required | numeric',
            'description',
            'date_in' => 'required | date',
            'date_out' => 'required | date',
            'room_id' => 'required | exists:room,id,deleted_at,NULL',
        ]);

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
        $booking->description = $input['description'];
        $booking->date_in = $input['date_in'];
        $booking->date_out = $input['date_out'];
        // date_booking is auto now + 7 hours
        $booking->date_booking = date('Y-m-d H:i:s', strtotime('+7 hours'));

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
            // kiểm tra xem ngày đặt phòng có trùng với ngày đặt phòng của người khác hay không và nếu trong booking detail có status = unpaid thì vẫn cho đặt phòng, status = pendding thì không cho đặt phòng
            $conflictingBooking = BookingDetail::where('room_id', $input['room_id'])
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('date_in', [$checkIn, $checkOut])
                        ->orWhereBetween('date_out', [$checkIn, $checkOut])
                        ->orWhere(function ($query) use ($checkIn, $checkOut) {
                            $query->where('date_in', '<=', $checkIn)
                                ->where('date_out', '>=', $checkOut);
                        });
                })->where('status', '!=', 'unpaid')
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
            $booking->save();

            $booking->payment()->create([
                'booking_id' => $booking->id,
                'total_amount' => $booking->total_amount,
                'qr_code_url' => 'https://buy.stripe.com/test_dR6g1ucrc6zWf7ybIL',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => [
                'booking' => $booking,
                'booking_detail' => $booking->bookingDetail()->get(),
                'payment' => $booking->payment()->get(),
                // get corect category of booking 
                'category' => $booking->bookingDetail()->first()->room->category,
                'category_image' => $booking->bookingDetail()->first()->room->category->categoryImage()->get(),
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $created_by = Hotel::find($id);
        if ($created_by == null) {
            return $this->sendError('Hotel not found.');
        }
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $created_by->created_by) {
            return $this->sendError('You are not authorized to do this action.');
        }

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
        $user = Auth::user();
        $created_by = Hotel::find($id);
        if ($created_by == null) {
            return $this->sendError('Hotel not found.');
        }
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $created_by->created_by) {
            return $this->sendError('You are not authorized to do this action.');
        }


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
        $user = Auth::user();
        $created_by = Hotel::find($id);
        if ($created_by == null) {
            return $this->sendError('Hotel not found.');
        }
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $created_by->created_by) {
            return $this->sendError('You are not authorized to do this action.');
        }

        // paginate and get status = pending
        $booking = Booking::where('hotel_id', $id)->where('status', 'pending')->get();

        if ($booking == null) {
            return $this->sendError('Booking not found.');
        }

        $bookingItem = [];

        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                [
                    'booking' => $value,
                    'user' => $value->user()->first(),
                    'category' => $value->hotel()->first()->category()->first(),
                ]
            ];
        }

        // for and dd($bookingItem);
        return response()->json([
            'success' => true,
            'message' => 'Booking retrieved successfully.',
            'data' => $bookingItem
        ], 200);

        // get 
        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                'booking' => $value,
                'booking_detail' => $value->bookingDetail()->get(),
                'payment' => $value->payment()->get(),
                'category' => $value->hotel()->first()->category()->first(),
                // 'room' => $value->bookingDetail()->first()->room()->first(),
            ];
        }

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
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

    public function getBookingByUserid($id)
    {
        $user = Auth::user();
        if ($user->role != 'user') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $id) {
            return $this->sendError('You are not get booking of another user.');
        }


        $booking = Booking::where('user_id', $id)->where('status', 'pending')->paginate(10);

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                [
                    'booking' => $value,
                    'payment' => $value->payment()->get(),
                    'category' => $value->bookingDetail()->first()->room()->first()->category()->first(),
                    'category_image' => $value->bookingDetail()->first()->room()->first()->category()->first()->categoryImage()->first(),
                ]
            ];
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem
            ], 200);
        }
    }

    public function getBookingByUserIdPast($id)
    {
        $user = Auth::user();
        if ($user->role != 'user') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $id) {
            return $this->sendError('You can not get booking of another user.');
        }

        // where != pending and unpaid
        $booking = Booking::where('user_id', $id)->where('status', '!=', 'pending')->where('status', '!=', 'unpaid')->get();


        foreach ($booking as $key => $value) {
            // $bookingdetail = $value->bookingDetail()->get();

            // get each booking detail 1 time
            $bookingdetail = $value->bookingDetail()->get()->unique('booking_id');

            foreach ($bookingdetail as $key => $value) {
                $bookingItem[] = [
                    [
                        'booking' => $value->booking()->first(),
                        'payment' => $value->booking()->first()->payment()->first(),
                        'category' => $value->room()->first()->category()->first(),
                        'category_image' => $value->room()->first()->category()->first()->categoryImage()->first(),
                    ]
                ];
            }
        };

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

    
        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem
            ], 200);
        }
    }

    public function getBookingByHotelIdPast($id)
    {
        $user = Auth::user();
        $created_by = Hotel::find($id);
        if ($created_by == null) {
            return $this->sendError('Hotel not found.');
        }
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $created_by->created_by) {
            return $this->sendError('You are not authorized to do this action.');
        }

        $booking = Booking::where('hotel_id', $id)->where('date_out', '<', Carbon::now())->paginate(20);

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                [
                    'booking' => $value,
                    'payment' => $value->payment()->get(),
                    'category' => $value->bookingDetail()->first()->room()->first()->category()->first(),
                ]
            ];
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem,
            ], 200);
        }
    }

    public function acceptBooking($id)
    {
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        // can not accept booking of another hotel
        $booking = Booking::find($id);
        if ($booking == null) {
            return $this->sendError('Booking not found.');
        }
        $hotel = Hotel::find($booking->hotel_id);
        $created_by = $hotel->created_by;
        if ($created_by != $user->id) {
            return $this->sendError('You can not accept booking of another hotel.');
        }

        // find booking with status = pending and id = $id
        $booking = Booking::where('id', $id)->where('status', 'pending')->first();

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }
        if ($booking->status == 'accepted') {
            return $this->sendError('Booking already accepted.');
        }

        $booking->status = 'accepted';
        $booking->save();

        if ($booking) {
            // update status in booking detail
            $bookingDetail = $booking->bookingDetail()->get();
            foreach ($bookingDetail as $key => $value) {
                $value->status = 'accepted';
                $value->save();
            }
        }


        $userBooking = User::find($booking->user_id)->id;
        // send notification to user
        $user = User::find($userBooking);
        $user->notify(new userReceiveBooking($booking));


        return response()->json([
            'success' => true,
            'message' => 'Booking accepted successfully.',
            'data' => $booking
        ], 200);
    }

    public function rejectBooking($id)
    {
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        // can not accept booking of another hotel
        $booking = Booking::find($id);
        if ($booking == null) {
            return $this->sendError('Booking not found.');
        }
        $hotel = Hotel::find($booking->hotel_id);
        $created_by = $hotel->created_by;
        if ($created_by != $user->id) {
            return $this->sendError('You can not reject booking of another hotel.');
        }

        // find booking with status = pending and id = $id
        $booking = Booking::where('id', $id)->where('status', 'pending')->first();

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }
        if ($booking->status == 'rejected') {
            return $this->sendError('Booking already rejected.');
        }

        $booking->status = 'rejected';
        $booking->save();

        if ($booking) {
            // delete booking detail
            $bookingDetail = $booking->bookingDetail()->get();
            foreach ($bookingDetail as $key => $value) {
                // change status to rejected
                $value->status = 'rejected';
                $value->save();
                $value->delete();
            }
        }

        $userBooking = User::find($booking->user_id)->id;
        // send notification to user
        $user = User::find($userBooking);
        $user->notify(new userRejectedBooking($booking));

        return $this->sendResponse(new BookingResource($booking), 'Booking rejected successfully.');
    }

    public function getBookingByHost($hostID)
    {
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $hostID) {
            return $this->sendError('You are not authorized to do this action.');
        }

        $allHotel = Hotel::where('created_by', $hostID)->get();

        foreach ($allHotel as $key => $value) {
            $allHotel[$key] = $value->id;
        }

        $booking = Booking::whereIn('hotel_id', $allHotel)->where('status', 'pending')->paginate(20);

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                [
                    'booking' => $value,
                    'payment' => $value->payment()->get(),
                    'category' => $value->bookingDetail()->first()->room()->first()->category()->first(),
                    'category_image' => $value->bookingDetail()->first()->room()->first()->category()->first()->categoryImage()->first(),
                ]
            ];
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem
            ], 200);
        }
    }


    public function getpassBookingByHost($hostID)
    {
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $hostID) {
            return $this->sendError('You are not authorized to do this action.');
        }

        $allHotel = Hotel::where('created_by', $hostID)->get();

        foreach ($allHotel as $key => $value) {
            $allHotel[$key] = $value->id;
        }

        // status != pending
        $booking = Booking::whereIn('hotel_id', $allHotel)->where('status', 'accepted')->paginate(20);

        if (is_null($booking)) {
            return $this->sendError('Booking not found.');
        }

        foreach ($booking as $key => $value) {
            $bookingItem[] = [
                [
                    'booking' => $value,
                    'payment' => $value->payment()->get(),
                    'category' => $value->bookingDetail()->first()->room()->first()->category()->first(),
                    'category_image' => $value->bookingDetail()->first()->room()->first()->category()->first()->categoryImage()->first(),
                ]
            ];
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem
            ], 200);
        }
    }

    public function getBookingRejectedByHost($idHost){
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to do this action.');
        }
        if ($user->id != $idHost) {
            return $this->sendError('You are not authorized to do this action.');
        }

        $allHotel = Hotel::where('created_by', $idHost)->get();

        foreach ($allHotel as $key => $value) {
            $allHotel[$key] = $value->id;
        }

        $booking = Booking::whereIn('hotel_id', $allHotel)->where('status', 'rejected')->get();

        foreach ($booking as $key => $value) {
            $bookingdetail = BookingDetail::where('booking_id', $value->id)->get()->unique('booking_id');

            foreach ($bookingdetail as $key => $value) {
                $bookingItem[] = [
                    [
                        'booking' => $value->booking()->first(),
                        'payment' => $value->booking()->first()->payment()->get(),
                        'category' => $value->room()->first()->category()->first(),
                        'category_image' => $value->room()->first()->category()->first()->categoryImage()->first(),
                    ]
                ];
            }
        }

        if (count($booking) == 0) {
            return $this->sendError('Booking not found.');
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Booking retrieved successfully.',
                'data' => $bookingItem
            ], 200);
        }
    }
}
