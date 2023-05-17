<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Resources\RoomResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Category;

class RoomController extends BaseController
{
    //
    public function index()
    {
        // get hotel image and category
        $rooms = Room::paginate();
        return $this->sendResponse(RoomResource::collection($rooms), 'Rooms retrieved successfully.');
    }

    public function show($id)
    {
        $room = Room::find($id);

        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        return $this->sendResponse(new RoomResource($room), 'Room retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_id' => 'required|exists:category,id,deleted_at,NULL',
            'name' =>  "required",
            'size' =>  "required| numeric | regex:/^\d+(\.\d+)?$/",
            'bed' =>  "required",
            'bathroom_facilities',
            'amenities',
            'directions_view',
            'description',
            'price' =>  "required",
            'max_people' =>  "required",
            'status' =>  "required",
            'is_smoking' =>  "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $room = Room::create($input);

        return $this->sendResponse(new RoomResource($room), 'Room created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_id' => 'required|exists:category,id,deleted_at,NULL',
            'name',
            'size',
            'bed',
            'bathroom_facilities',
            'amenities',
            'directions_view',
            'description',
            'price',
            'max_people',
            'status',
            'is_smoking',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $room = Room::find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $room->category_id = $input['category_id'];
        $room->name = $input['name'];
        $room->size = $input['size'];
        $room->bed = $input['bed'];
        $room->bathroom_facilities = $input['bathroom_facilities'];
        $room->amenities = $input['amenities'];
        $room->directions_view = $input['directions_view'];
        $room->description = $input['description'];
        $room->price = $input['price'];
        $room->max_people = $input['max_people'];
        $room->status = $input['status'];
        $room->is_smoking = $input['is_smoking'];

        if ($room->save()) {
            return $this->sendResponse(new RoomResource($room), 'Room updated successfully.');
        } else {
            return $this->sendError('Room not updated.');
        }
    }

    public function destroy($id)
    {
        $room = Room::find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $roomImage = $room->roomImage;
        if ($room->delete()) {
            foreach ($roomImage as $image) {
                $image->delete();
            }
        } else {
            return $this->sendError('Room not deleted.');
        }

        return $this->sendResponse([], 'Room deleted successfully.');
    }

    public function restore($id)
    {
        // restore room then restore room image
        $room = Room::onlyTrashed()->find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $roomImage = $room->roomImage()->onlyTrashed()->get();

        if ($room->restore()) {
            foreach ($roomImage as $image) {
                $image->restore();
            }
        } else {
            return $this->sendError('Room not restored.');
        }

        return $this->sendResponse([], 'Room restored successfully.');
    }

    public function restoreByHotelId($id)
    {
        //
        $category = Category::onlyTrashed()->where('hotel_id', $id)->get();
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        if ($category->count() > 0) {
            foreach ($category as $c) {
                $room = Room::onlyTrashed()->where('category_id', $c->id)->get();
                if ($room->count() > 0) {
                    foreach ($room as $r) {
                        $r->restore();
                    }
                }
            }
            return $this->sendResponse([], 'Room restored successfully.');
        } else {
            return $this->sendError('Room not restored.');
        }
    }

    public function restoreByCategoryId($id)
    {
        $room = Room::onlyTrashed()->where('category_id', $id)->get();
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }
        if ($room->count() > 0) {
            foreach ($room as $r) {
                $r->restore();
            }
            return $this->sendResponse([], 'Room restored successfully.');
        } else {
            return $this->sendError('Room not restored.');
        }
    }

    public function getRoomByHotelId($id)
    {
        $category = Category::where('hotel_id', $id)->get();
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $rooms = [];
        if ($category->count() > 0) {
            foreach ($category as $c) {
                $room = Room::where('category_id', $c->id)->get();
                if ($room->count() > 0) {
                    foreach ($room as $r) {
                        array_push($rooms, $r);
                    }
                }
            }
            return response()->json($rooms);
        } else {
            return $this->sendError('Room not found.');
        }
    }

    public function getPriceByRoomId($id)
    {
        $room = Room::find($id);

        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        return response()->json($room->price);
    }

    // get array of price of room by hotel id
    public function getAllPriceByHotelId($id){
        $category = Category::where('hotel_id', $id)->get();
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $rooms = [];
        if ($category->count() > 0) {
            foreach ($category as $c) {
                $room = Room::where('category_id', $c->id)->get();
                if ($room->count() > 0) {
                    foreach ($room as $r) {
                        array_push($rooms, $r->price);
                    }
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'Hotel retrieved successfully.',
                'data' => $rooms,
            ]);
        } else {
            return $this->sendError('Hotel ID not found.');
        }
    }

    public function CountRoomByCategoryId($id)
    {
        $room = Room::where('category_id', $id)->get()->count();
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }
        return response()->json($room);
    }

    public function updatePriceByRoomId(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'price' =>  "required|numeric",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $room = Room::find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $room->price = $input['price'];

        if ($room->save()) {
            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully.',
                'data' => $room,
            ]);
        } else {
            return $this->sendError('Room not updated.');
        }
    }
}
