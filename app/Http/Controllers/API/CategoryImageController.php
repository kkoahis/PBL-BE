<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CategoryImage;
use App\Http\Resources\CategoryImageResource;
use App\Http\Requests\CategoryImageRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Http\Controllers\API\BaseController as BaseController;

class CategoryImageController extends BaseController
{
    public function index()
    {
        // get hotel image and category
        $categoryImages = CategoryImage::get();
        return $this->sendResponse(CategoryImageResource::collection($categoryImages), 'CategoryImages retrieved successfully.');
    }

    public function show($id)
    {
        $categoryImage = CategoryImage::find($id);

        if (is_null($categoryImage)) {
            return $this->sendError('CategoryImage not found.');
        }

        return $this->sendResponse(new CategoryImageResource($categoryImage), 'CategoryImage retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_id' => 'required|exists:category,id,deleted_at,NULL',
            'image_url' =>  "required",
            'image_description'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = auth()->user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($input['category_id']);
            $hotel = $category->hotel;

            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }

            if ($hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to add image to this hotel.');
            }

            $categoryImage = CategoryImage::create($input);
            return $this->sendResponse(new CategoryImageResource($categoryImage), 'CategoryImage created successfully.');
        } else {
            return $this->sendError('You are not authorized to add image to this hotel.');
        }
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_id' => 'required|exists:category,id,deleted_at,NULL',
            'image_url' =>  "required",
            'image_description'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = auth()->user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($input['category_id']);
            $hotel = $category->hotel;

            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }

            if ($hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to add image to this hotel.');
            }

            $categoryImage = CategoryImage::find($id);
            if (is_null($categoryImage)) {
                return $this->sendError('CategoryImage not found.');
            }
            $categoryImage->update($input);
            return $this->sendResponse(new CategoryImageResource($categoryImage), 'CategoryImage updated successfully.');
        } else {
            return $this->sendError('You are not authorized to add image to this hotel.');
        }

        $categoryImage = CategoryImage::find($id);
        if (is_null($categoryImage)) {
            return $this->sendError('CategoryImage not found.');
        }
        $categoryImage->category_id = $input['category_id'];
        $categoryImage->image_url = $input['image_url'];
        $categoryImage->image_description = $input['image_description'];
        $categoryImage->save();

        return $this->sendResponse(new CategoryImageResource($categoryImage), 'CategoryImage updated successfully.');
    }

    public function destroy($id)
    {
        $categoryImage = CategoryImage::find($id);
        if (is_null($categoryImage)) {
            return $this->sendError('CategoryImage not found.');
        }

        $user = auth()->user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($categoryImage->category_id);
            $hotel = $category->hotel;

            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }

            if ($hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to add image to this hotel.');
            }
        }

        $categoryImage->delete();

        return $this->sendResponse([], 'CategoryImage deleted successfully.');
    }

    public function deleteByCategoryId($id){
        $categoryImage = CategoryImage::where('category_id', $id);

        $user = auth()->user();

        if ($user->role == 'hotel') {
            // get hotel id from input id
            $category = Category::find($id);
            $hotel = $category->hotel;

            if (is_null($category)) {
                return $this->sendError('Category ID not found.');
            }

            if ($hotel->created_by != $user->id) {
                return $this->sendError('You are not authorized to add image to this hotel.');
            }
        }

        if (is_null($categoryImage)) {
            return $this->sendError('CategoryImage not found.');
        }

        if ($categoryImage->delete()) {
            return $this->sendResponse([], 'CategoryImage deleted successfully.');
        } else {
            return $this->sendError('CategoryImage not found.');
        }
    }

    public function restoreByCategoryId($id){
        $categoryImage = CategoryImage::onlyTrashed()->where('category_id', $id);

        $user = auth()->user();

        if($user->role == 'hotel'){
            // get hotel id from input id
            $category = Category::find($id);
            $hotel = $category->hotel;

            if(is_null($category)){
                return $this->sendError('Category ID not found.');
            }

            if($hotel->created_by != $user->id){
                return $this->sendError('You are not authorized to add image to this hotel.');
            }
        }

        if(is_null($categoryImage)){
            return $this->sendError('CategoryImage not found.');
        }

        if($categoryImage->restore()){
            return $this->sendResponse([], 'CategoryImage restored successfully.');
        } else {
            return $this->sendError('CategoryImage not found.');
        }
    }

    public function getImageByCategoryId($id){
        $categoryImage = CategoryImage::where('category_id', $id)->get();

        if(is_null($categoryImage)){
            return $this->sendError('CategoryImage not found.');
        }

        return $this->sendResponse(CategoryImageResource::collection($categoryImage), 'CategoryImage retrieved successfully.');
    }
}
