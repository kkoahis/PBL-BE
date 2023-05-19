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
use App\Models\Hotel;
use Illuminate\Support\Facades\Auth;

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

        if ($user->role == 'admin') {
            $hotel = Hotel::firstOrFail($input['hotel_id']);
            if (is_null($hotel)) {
                return $this->sendError('Hotel ID not found.');
            }
        } else {
            // get hotel id from input id, then check if hotel is created by user
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
        if ($user->role == 'admin') {
            // get hotel id from input id from request id url
            $hotel = Hotel::firstOrFail($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('Hotel ID not found.');
            }
        } else {
            // get hotel id from input id, then check if hotel is created by user
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

    public function destroy($id)
    {
        $category = Category::find($id);
        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $user = Auth::user();

        if ($user->role == 'admin') {
            // get hotel id from input id from request id url
            $hotel = Hotel::firstOrFail($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('Hotel ID not found.');
            }
        } else {
            // get hotel id from input id, then check if hotel is created by user
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to add category to this hotel.');
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

        if (is_null($category)) {
            return $this->sendError('Category not found.');
        }

        $user = Auth::user();

        if ($user->role == 'admin') {
            // get hotel id from input id from request id url
            $hotel = Hotel::firstOrFail($id);
            if (is_null($hotel)) {
                return $this->sendError('Hotel ID not found.');
            }
        } else {
            // get hotel id from input id, then check if hotel is created by user
            $hotel = Hotel::where('created_by', $user->id)->find($id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to add category to this hotel.');
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
        if ($user->role == 'admin') {
            // get hotel id from input id from request id url
            $hotel = Hotel::firstOrFail($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('Hotel ID not found.');
            }
        } else {
            // get hotel id from input id, then check if hotel is created by user
            $hotel = Hotel::where('created_by', $user->id)->find($category->hotel_id);
            if (is_null($hotel)) {
                return $this->sendError('You are not authorized to add category to this hotel.');
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

            if ($user->role == 'admin') {
                // get hotel id from input id from request id url
                $hotel = Hotel::firstOrFail($id);
                if (is_null($hotel)) {
                    return $this->sendError('Hotel ID not found.');
                }
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

    public function getPriceByCategoryId($id){
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
}
