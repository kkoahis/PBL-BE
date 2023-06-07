<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PaymentController extends BaseController
{
    //
    public function index()
    {
        $payments = Payment::paginate();
        return $this->sendResponse(PaymentResource::collection($payments), 'Payments retrieved successfully.');
    }

    public function show($id)
    {
        $payment = Payment::find($id);

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        return $this->sendResponse(new PaymentResource($payment), 'Payment retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            // reach booking_id has one payment
            'booking_id' => 'required|exists:booking,id,deleted_at,NULL',
            'qr_code',
            'qr_code_url',
            'total_amount' => 'required',
            'payment_status' => 'required',
            'discount',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $payment = Payment::create($input);

        return $this->sendResponse(new PaymentResource($payment), 'Payment created successfully.');
    }

    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'booking_id' => 'required|exists:booking,id,deleted_at,NULL',
            'payment_status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $booking_id = $input['booking_id'];
        // search user_id by booking_id
        $user_id = Booking::where('id', $booking_id)->first()->user_id;

        if (Auth::user()->id != $user_id) {
            return $this->sendError('You are not authorized to update this payment.');
        }

        $payment = Payment::where('booking_id', $booking_id)->first();

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        $payment->payment_status = $input['payment_status'];

        // if payment status is success
        if ($payment->payment_status == 1) {
            // update booking status
            $booking = $payment->booking;
            $booking->status = 'pending';
            $booking->save();

            // also update booking detail status
            $booking_details = $booking->bookingDetail->map(function ($booking_detail) {
                return $booking_detail;
            });

            // update booking detail status
            foreach ($booking_details as $booking_detail) {
                $booking_detail->status = 'pending';
                $booking_detail->save();
            }

            return $this->sendResponse(new PaymentResource($payment), 'Payment updated successfully.');
        }
        return $this->sendError('Payment can not be updated.');
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (is_null($payment)) {
            return $this->sendError('Payment ID not found.');
        }
        if ($payment->delete()) {
            return $this->sendResponse([], 'Payment deleted successfully.');
        }

        return $this->sendError('Payment can not be deleted.');
    }

    public function getPaymentByBookingId($booking_id)
    {
        $payment = Payment::where('booking_id', $booking_id)->first();

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        return $this->sendResponse(new PaymentResource($payment), 'Payment retrieved successfully.');
    }

    public function deleteByBookingId($booking_id)
    {
        $payment = Payment::where('booking_id', $booking_id)->first();

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        if ($payment->delete()) {
            return $this->sendResponse([], 'Payment deleted successfully.');
        }

        return $this->sendError('Payment can not be deleted.');
    }

    // public function deleteByHotelId($hotel_id){
    //     $hotel = Hotel::find($hotel_id);
    //     if (is_null($hotel)) {
    //         return $this->sendError('Hotel not found.');
    //     }

    //     $payment = Payment::whereHas('booking', function ($query) use ($hotel_id) {
    //         $query->where('hotel_id', $hotel_id);
    //     })->get();

    //     if (is_null($payment)) {
    //         return $this->sendError('Payment not found.');
    //     }

    //     if ($payment->delete()) {
    //         return $this->sendResponse([], 'Payment deleted successfully.');
    //     }

    //     return $this->sendError('Payment can not be deleted.');
    // }
}
