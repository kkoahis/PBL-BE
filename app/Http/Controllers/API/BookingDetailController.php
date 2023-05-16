<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\BookingDetail;
use App\Http\Resources\BookingDetailResource;

class BookingDetailController extends BaseController
{
    //
    public function index()
    {
        // $bookingDetails = BookingDetail::get();
        // return $this->sendResponse(BookingDetailResource::collection($bookingDetails), 'Booking Details retrieved successfully.');
        
        $bookingDetail = BookingDetail::paginate(30);
        return response()->json(([
            'success' => true,
            'message' => 'Booking Detail retrieved successfully.',
            'data' => $bookingDetail,
        ]
        ));
    }

    public function show($id)
    {
        $bookingDetail = BookingDetail::find($id);

        if (is_null($bookingDetail)) {
            return $this->sendError('Booking Detail not found.');
        }

        return $this->sendResponse(new BookingDetailResource($bookingDetail), 'Booking Detail retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_id' => 'required|exists:booking,id,deleted_at,NULL',
            'room_id' => 'required|exists:room,id,deleted_at,NULL',
            'created_at' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $bookingDetail = BookingDetail::create($input);

        return $this->sendResponse(new BookingDetailResource($bookingDetail), 'Booking Detail created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'booking_id' => 'required|exists:booking,id,deleted_at,NULL',
            'room_id' => 'required|exists:room,id,deleted_at,NULL',
            'created_at' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $bookingDetail = BookingDetail::find($id);
        if (is_null($bookingDetail)) {
            return $this->sendError('Booking Detail not found.');
        }
        $bookingDetail->booking_id = $input['booking_id'];
        $bookingDetail->room_id = $input['room_id'];
        $bookingDetail->created_at = $input['created_at'];
        $bookingDetail->save();

        return $this->sendResponse(new BookingDetailResource($bookingDetail), 'Booking Detail updated successfully.');
    }

    public function destroy($id)
    {
        $bookingDetail = BookingDetail::find($id);

        if (is_null($bookingDetail)) {
            return $this->sendError('Booking Detail not found.');
        }
        if ($bookingDetail->delete()) {
            return $this->sendResponse([], 'Booking Detail deleted successfully.');
        }
        return $this->sendError('Booking Detail can not delete.');
    }

    public function restore($id)
    {
        $bookingDetail = BookingDetail::onlyTrashed()->find($id);

        if (is_null($bookingDetail)) {
            return $this->sendError('Booking Detail not found.');
        }
        if ($bookingDetail->restore()) {
            return $this->sendResponse([], 'Booking Detail restored successfully.');
        }
        return $this->sendError('Booking Detail can not restore.');
    }

    public function getBookingDetailByRoomId($id)
    {
        $bookingDetail = BookingDetail::where('room_id', $id)->paginate(30);

        if (is_null($bookingDetail)) {
            return $this->sendError('Room ID not found.');
        }
        if (count($bookingDetail) == 0) {
            return $this->sendError('Room IDnot found.');
        } else {
            return $this->sendResponse(BookingDetailResource::collection($bookingDetail), 'Booking retrieved successfully.');
        }
    }

    public function getBookingDetailByBookingId($id){
        $bookingDetail = BookingDetail::where('booking_id', $id)->paginate(30);

        if (is_null($bookingDetail)) {
            return $this->sendError('Booking ID not found.');
        }
        if (count($bookingDetail) == 0) {
            return $this->sendError('Booking ID not found.');
        } else {
            return $this->sendResponse(BookingDetailResource::collection($bookingDetail), 'Booking retrieved successfully.');
        }
    }
}
