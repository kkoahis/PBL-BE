<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use App\Models\Hotel;

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

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            // 'booking_id' => 'required',
            'qr_code',
            'qr_code_url',
            'total_amount',
            'payment_status',
            'discount',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $payment = Payment::find($id);

        if (is_null($payment)) {
            return $this->sendError('Payment not found.');
        }

        // $payment->booking_id = $input['booking_id'];
        $payment->qr_code = $input['qr_code'];
        $payment->qr_code_url = $input['qr_code_url'];
        $payment->total_amount = $input['total_amount'];
        $payment->payment_status = $input['payment_status'];
        $payment->discount = $input['discount'];
        $payment->save();

        return $this->sendResponse(new PaymentResource($payment), 'Payment updated successfully.');
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
