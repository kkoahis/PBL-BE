<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Room;
use App\Models\RoomImage;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CategoryResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Hotel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryController extends BaseController
{
    //
    public function index()
    {
        $categories = Category::get();
        return $this->sendResponse(CategoryResource::collection($categories), 'Categories retrieved successfully.');
    }

    public function show($id)
    {
        $category = Category::with('hotel')->whereHas('hotel', function ($query) {
            $query->whereNull('deleted_at');
        })->find($id);

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        return $this->sendResponse(new CategoryResource($category), 'Category retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            // if hotel is soft deleted, then send error response
            'hotel_id' => 'required|exists:hotel,id,deleted_at,NULL',
            'description',
            // 'hotel_id' => 'required',

            'size' =>  "required | regex:/^\d+(\.\d+)?$/",
            'bed' =>  "required  | numeric",
            'bathroom_facilities',
            'amenities',
            'directions_view',
            'description',
            'price' =>  "required | regex:/^\d+(\.\d+)?$/",
            'max_people' =>  "required ",
            'is_smoking' =>  "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = Auth::user();

        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to add category to this hotel.');
        }
        if ($user->role == 'hotel') {
            $hotel = Hotel::where('created_by', $user->id)->find($input['hotel_id']);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to add category to this hotel.');
            }
        }

        $category = new Category();
        $category->name = $input['name'];
        $category->description = $input['description'];
        $category->hotel_id = $input['hotel_id'];
        $category->size = $input['size'];
        $category->bed = $input['bed'];
        $category->bathroom_facilities = $input['bathroom_facilities'];
        $category->amenities = $input['amenities'];
        $category->directions_view = $input['directions_view'];
        $category->description = $input['description'];
        $category->price = $input['price'];
        $category->max_people = $input['max_people'];
        $category->is_smoking = $input['is_smoking'];

        $category->save();

        return $this->sendResponse(new CategoryResource($category), 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'description',
            // 'hotel_id' => 'required',
            'size' =>  "required | regex:/^\d+(\.\d+)?$/",
            'bed' =>  "required  | numeric",
            'bathroom_facilities',
            'amenities',
            'directions_view',
            'description',
            'price' =>  "required | regex:/^\d+(\.\d+)?$/",
            'max_people' =>  "required ",
            'is_smoking' =>  "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $category = Category::find($id);
        // if category is soft deleted, then send error response
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }
        $category->name = $input['name'];
        $category->description = $input['description'];
        // $category->hotel_id = $input['hotel_id'];
        $category->size = $input['size'];
        $category->bed = $input['bed'];
        $category->bathroom_facilities = $input['bathroom_facilities'];
        $category->amenities = $input['amenities'];
        $category->directions_view = $input['directions_view'];
        $category->description = $input['description'];
        $category->price = $input['price'];
        $category->max_people = $input['max_people'];
        $category->is_smoking = $input['is_smoking'];

        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to update category to this hotel.');
        }
        if ($user->role == 'hotel') {
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to update category to this hotel.');
            }
        }

        $category->save();

        return response()->json(([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category,
            ])
        );
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $user = Auth::user();

        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to delete category to this hotel.');
        }
        if ($user->role == 'hotel') {
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to delete category to this hotel.');
            }
        }

        $room = $category->room;
        // echo $roomImage;
        $categoryImage = $category->categoryImage;

        if ($category->delete()) {
            foreach ($room as $item) {
                $item->delete();
            }

            foreach ($categoryImage as $item) {
                $item->delete();
            }

            return $this->sendResponse([], 'Category deleted successfully.');
        }

        return $this->sendError('Category not found.');
    }

    public function deleteCategoryByHotelId($id)
    {
        $category = Category::where('hotel_id', $id)->get();

        if ($category->count() == 0) {
            return $this->sendError('Category not found.');
        }

        $user = Auth::user();

        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to delete category to this hotel.');
        } else {
            // get hotel id from input id, then check if hotel is created by user
            $hotel = Hotel::where('created_by', $user->id)->find($id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to delete category to this hotel.');
            }
        }

        $room = $category->map(function ($item) {
            return $item->room;
        });

        $categoryImage = $category->map(function ($item) {
            return $item->categoryImage;
        });

        if ($category->count() > 0) {
            foreach ($category as $item) {
                $item->delete();
            }

            foreach ($room as $item) {
                foreach ($item as $i) {
                    $i->delete();
                }
            }

            foreach ($categoryImage as $item) {
                foreach ($item as $i) {
                    $i->delete();
                }
            }

            return $this->sendResponse([], 'Category deleted successfully.');
        } else {
            return $this->sendError('Category not found.');
        }
    }

    public function restore($id)
    {
        // restore cate then restore room and room image
        $category = Category::onlyTrashed()->find($id);
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to restore category to this hotel.');
        } else {
            // get hotel id from input id, then check if hotel is created by user
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to restore category to this hotel.');
            }
        }

        $room = $category->room()->onlyTrashed()->get();
        $categoryImage = $category->categoryImage()->onlyTrashed()->get();

        // echo $room;
        // echo $roomImage;

        if ($category->restore()) {
            foreach ($room as $item) {
                $item->restore();
            }

            foreach ($categoryImage as $item) {
                $item->restore();
            }

            return $this->sendResponse([], 'Category restored successfully.');
        }

        return $this->sendError('Category not found.');
    }

    public function restoreByHotelId($id)
    {
        $category = Category::onlyTrashed()->where('hotel_id', $id)->get();

        if (is_null($category)) {
            return $this->sendError('Hotel ID not found.');
        }

        if ($category->count() > 0) {

            $room = $category->map(function ($item) {
                return $item->room()->onlyTrashed()->get();
            });

            $categoryImage = $category->map(function ($item) {
                return $item->categoryImage()->onlyTrashed()->get();
            });

            $user = Auth::user();

            if ($user->role != 'hotel') {
                return $this->sendError('You are not authorized to add category to this hotel.');
            } else {
                // get hotel id from input id, then check if hotel is created by user
                $hotel = Hotel::where('created_by', $user->id)->find($id);
                if (is_null($hotel)) {
                    return $this->sendError('You are not authorized to add category to this hotel.');
                }
            }


            foreach ($category as $item) {
                $item->restore();
            }

            foreach ($room as $item) {
                foreach ($item as $i) {
                    $i->restore();
                }
            }

            foreach ($categoryImage as $item) {
                foreach ($item as $i) {
                    $i->restore();
                }
            }

            return $this->sendResponse([], 'Category restored successfully.');
        } else {
            return $this->sendError('Hotel ID restore not found.');
        }
    }

    public function updatePriceByCategoryId(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'price' =>  "required | regex:/^\d+(\.\d+)?$/",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $category = Category::find($id);
        // if category is soft deleted, then send error response
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }
        $category->price = $input['price'];

        $user = Auth::user();
        if ($user->role == 'hotel') {
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to add category to this hotel.');
            }
        }

        $category->save();

        return response()->json(([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => $category,
            ])
        );
    }

    public function getCategoryByHotelId($id)
    {
        $category = Category::where('hotel_id', $id)->with('categoryImage')->get();

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        return response()->json(([
                'success' => true,
                'message' => 'Category retrieved successfully.',
                'data' => [
                    'category' => $category,
                ]
            ])
        );
    }

    public function getPriceByCategoryId($id)
    {
        $category = Category::find($id);

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        return response()->json(([
                'success' => true,
                'message' => 'Category retrieved successfully.',
                'data' => [
                    'price' => $category->price,
                ]
            ])
        );
    }


    public function getCategoryByCheckInCheckOut(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'date_in' => 'required | date',
            'date_out' => 'required | date | after:date_in',
            'category_id' => 'required | integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $date_in = $input['date_in'];
        $date_out = $input['date_out'];
        $category_id = $input['category_id'];

        // get all room id from category id
        $room = Room::where('category_id', $category_id)->get()->pluck('id');

        // get booking detail with date_in and date_out
        $bookingDetail = BookingDetail::where('date_in', '<=', $date_in)
            ->where('date_out', '>=', $date_out)
            ->whereIn('room_id', $room)
            ->get();

        // get all room id from booking detail
        $roomBooked = $bookingDetail->pluck('room_id');

        // echo $roomBooked . "\n" . $room; 

        // inner join to get room that is not booked
        $roomAvailable = Room::where('category_id', $category_id)
            ->whereNotIn('id', $roomBooked)
            ->get();

        // echo $roomAvailable->count();

        // // get category from room available
        $category = $roomAvailable->map(function ($item) {
            return $item->category;
        });

        return response()->json(([
                'success' => true,
                'message' => 'Category retrieved successfully.',
                'data' => [
                    'room available' => $roomAvailable->count(),
                ]
            ])
        );
    }

    public function getCategoryByCheckInCheckOutHotel(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'date_in' => 'required | date',
            'date_out' => 'required | date | after:date_in',
            'hotel_id' => 'required | integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $date_in = $input['date_in'];
        $date_out = $input['date_out'];
        $hotel_id = $input['hotel_id'];

        try {
            DB::beginTransaction();

            $category = Category::where('hotel_id', $hotel_id)->get()->pluck('id');
            $allRoom = Room::whereIn('category_id', $category)->get()->pluck('id');

            // get booking detail with date_in and date_out
            $bookingDetail = BookingDetail::where('date_in', '<=', $date_in)
                ->where('date_out', '>=', $date_out)
                ->where('status', '!=', 'unpaid')
                ->whereIn('room_id', $allRoom)
                ->get();
            // get 'not get booking detail that soft deleted'

            // if no booking detail, then all room is available
            if ($bookingDetail->count() == 0) {
                $roomAvailable = Room::whereIn('id', $allRoom)->get();
                $categoryDisplay = [];
                $roomCountPerDisplay = [];
                $roomIDPerDisplay = [];

                foreach ($category as $key => $value) {
                    $categoryDisplay[$key] = Category::find($value);
                    $roomCountPerDisplay[$key] = $roomAvailable->where('category_id', $value)->count();
                    $roomIDPerDisplay[$key] = $roomAvailable->where('category_id', $value)->pluck('id');
                }

                $data = [
                    'category' => $categoryDisplay,
                    'roomCountPerDisplay' => $roomCountPerDisplay,
                    'roomIDPerDisplay' => $roomIDPerDisplay,
                ];

                return response()->json(([
                        'success' => true,
                        'message' => 'Category retrieved successfully.',
                        'data' => $data,
                    ])
                );
            }

            $roomBooked = $bookingDetail->pluck('room_id');

            $roomAvailable = Room::whereIn('id', $allRoom)
                ->whereNotIn('id', $roomBooked)
                ->get();

            $categoryDisplay = [];
            $roomCountPerDisplay = [];
            $roomIDPerDisplay = [];

            // if request again, then refresh roomIDPerDisplay and roomCountPerDisplay
            if (Cache::has('roomIDPerDisplay')) {
                Cache::forget('roomIDPerDisplay');
            }
            if (Cache::has('roomCountPerDisplay')) {
                Cache::forget('roomCountPerDisplay');
            }

            foreach ($category as $key => $value) {
                $categoryDisplay[$key] = Category::find($value);
                // also get category image per category
                // $categoryDisplay[$key]['category_image'] = $categoryDisplay[$key]->categoryImage;
                $roomCountPerDisplay[$key] = $roomAvailable->where('category_id', $value)->count();
                $roomIDPerDisplay[$key] = $roomAvailable->where('category_id', $value)->pluck('id');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully.',
                'data' => [
                    'category' => $categoryDisplay,
                    'roomCountPerDisplay' => $roomCountPerDisplay,
                    'roomIDPerDisplay' => $roomIDPerDisplay,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Error retrieving categories.', $e->getMessage());
        }
    }
}
