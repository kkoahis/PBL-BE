<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Http\Resources\RoomResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

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
            // 'status' =>  "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = Auth::user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($input['category_id']);
            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }
            if ($category->hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to add room to this hotel.');
            }
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
            'status',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $room = Room::find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $user = Auth::user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($input['category_id']);
            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }
            if ($category->hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to update room to this hotel.');
            }
        }

        $room->category_id = $input['category_id'];
        $room->name = $input['name'];
        $room->status = $input['status'];

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

        $user = Auth::user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($room->category_id);
            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }
            if ($category->hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to delete room to this hotel.');
            }
        }

        if ($room->delete()) {
            return $this->sendResponse([], 'Room deleted successfully.');
        } else {
            return $this->sendError('Room not deleted.');
        }
    }

    public function restore($id)
    {
        // restore room then restore room image
        $room = Room::onlyTrashed()->find($id);
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }

        $user = Auth::user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($room->category_id);
            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }
            if ($category->hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to restore room to this hotel.');
            }
        }


        if ($room->restore()) {
            return $this->sendResponse([], 'Room restored successfully.');
        } else {
            return $this->sendError('Room not restored.');
        }
    }

    public function restoreByHotelId($id)
    {
        $category = Category::withTrashed()->where('hotel_id', $id)->get();

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        if ($category->count() > 0) {
            $user = Auth::user();

            if ($user->role == 'hotel') {
                // get hotel id from input id
                $hotel = $category[0]->hotel;
                if (is_null($hotel)) {
                    return $this->sendError('Hotel ID not found.');
                }
                if ($hotel->created_by != $user->id) {
                    return $this->sendError('You are not authorized to restore room to this hotel.');
                }
            }

            foreach ($category as $c) {
                $room = Room::onlyTrashed()->where('category_id', $c->id)->get();
                if ($room->count() > 0) {
                    foreach ($room as $r) {
                        $r->restore();
                    }
                }
                return $this->sendResponse([], 'Room restored successfully.');
            }
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

            $user = Auth::user();
            if ($user->role == 'hotel') {
                $category = Category::find($id);
                if (is_null($category)) {
                    return $this->sendError('Category ID not found.');
                }
                if ($category->hotel->created_by != $user->id) {
                    return $this->sendError('You are not authorized to restore room to this hotel.');
                }
            }

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

    public function CountRoomByCategoryId($id)
    {
        $room = Room::where('category_id', $id)->get()->count();
        if (is_null($room)) {
            return $this->sendError('Room not found.');
        }
        return response()->json($room);
    }
}
