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
            'description',
            // if hotel is soft deleted, then send error response
            'hotel_id' => 'required|exists:hotel,id,deleted_at,NULL',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $category = Category::create($input);

        return $this->sendResponse(new CategoryResource($category), 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'description',
            // 'hotel_id' => 'required',
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

        $room = $category->room;
        $roomImage = $category->room->map(function ($item) {
            return $item->roomImage;
        });
        // echo $roomImage;

        if ($category->delete()) {
            foreach ($room as $item) {
                $item->delete();
            }

            foreach ($roomImage as $r) {
                foreach ($r as $ro) {
                    $ro->delete();
                }
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
        if ($category->count() > 0) {
            foreach ($category as $item) {
                $item->delete();
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

        $room = $category->room()->onlyTrashed()->get();
        $roomImage = $category->room()->onlyTrashed()->get()->map(function ($item) {
            return $item->roomImage()->onlyTrashed()->get();
        });

        // echo $room;
        // echo $roomImage;

        if ($category->restore()) {
            foreach ($room as $item) {
                $item->restore();
            }

            foreach ($roomImage as $r) {
                foreach ($r as $ro) {
                    $ro->restore();
                }
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
            foreach ($category as $item) {
                $item->restore();
            }
            return $this->sendResponse([], 'Category restored successfully.');
        } else {
            return $this->sendError('Hotel ID restore not found.');
        }
    }
}
