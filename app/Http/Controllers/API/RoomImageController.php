<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\RoomImage;
use App\Http\Resources\RoomImageResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class RoomImageController extends BaseController
{
    //
    public function index()
    {
        // get hotel image and category
        $roomImages = RoomImage::get();
        return $this->sendResponse(RoomImageResource::collection($roomImages), 'RoomImages retrieved successfully.');
    }

    public function show($id)
    {
        $roomImage = RoomImage::find($id);

        if (is_null($roomImage)) {
            return $this->sendError('RoomImage not found.');
        }

        return $this->sendResponse(new RoomImageResource($roomImage), 'RoomImage retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'room_id' => 'required|exists:room,id,deleted_at,NULL',
            'image_url' =>  "required",
            'image_description'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $roomImage = RoomImage::create($input);

        return $this->sendResponse(new RoomImageResource($roomImage), 'RoomImage created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'room_id' => 'required|exists:room,id,deleted_at,NULL',
            'image_url' =>  "required",
            'image_description'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $roomImage = RoomImage::find($id);
        if (is_null($roomImage)) {
            return $this->sendError('RoomImage not found.');
        }
        $roomImage->room_id = $input['room_id'];
        $roomImage->image_url = $input['image_url'];
        $roomImage->image_description = $input['image_description'];
        $roomImage->save();

        return $this->sendResponse(new RoomImageResource($roomImage), 'RoomImage updated successfully.');
    }

    public function deleteImageByRoomId($id)
    {
        // find id and delete
        $roomImage = RoomImage::where('room_id', $id);
        if ($roomImage->delete()) {
            return $this->sendResponse([], 'RoomImage deleted successfully.');
        }
        return $this->sendError('RoomImage not found.');
    }

    public function restoreByRoomId($id)
    {
        $roomImage = RoomImage::onlyTrashed()->where('room_id', $id);
        if ($roomImage->restore()) {
            return $this->sendResponse([], 'RoomImage restored successfully.');
        }
        return $this->sendError('RoomImage not found.');
    }

    public function getImageByRoomId($id)
    {
        $roomImage = RoomImage::where('room_id', $id)->get();
        if (is_null($roomImage)) {
            return $this->sendError('RoomImage not found.');
        }
        if (count($roomImage) == 0) {
            return $this->sendError('RoomImage not found.');
        }
        return $this->sendResponse(RoomImageResource::collection($roomImage), 'RoomImage retrieved successfully.');
    }
}
