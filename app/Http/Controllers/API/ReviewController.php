<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ReviewController extends BaseController
{
    //
    public function index()
    {
        // return hotels with category and in category get all rooms
        $reviews = Review::paginate();
        return $this->sendResponse(ReviewResource::collection($reviews), 'Reviews retrieved successfully.');
    }

    public function show($id)
    {
        $review = Review::find($id);

        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        return $this->sendResponse(new ReviewResource($review), 'Review retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            // 'user_id' => 'required|exists:users,id,deleted_at,NULL',
            'booking_id' => 'required|exists:booking,id,deleted_at,NULL',
            'title' => 'required',
            'content' => 'required ',
            'rating' => 'required | numeric | min:1 | max:5'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $token = PersonalAccessToken::findToken($request->bearerToken());

        $user = User::find($token->tokenable_id);
        $user_id = $user->id;

        if (Booking::find($input['booking_id'])->status != 'accepted') {
            return $this->sendError('Booking not completed.');
        }
        if (Review::where('booking_id', $input['booking_id'])->exists()) {
            return $this->sendError('Review already exists.');
        }


        $review = new Review();
        $review->user_id = $user_id;
        $review->booking_id = $input['booking_id'];
        $review->title = 'title';
        $review->content = $input['content'];
        $review->rating = $input['rating'];

        $review->save();

        return $this->sendResponse(new ReviewResource($review), 'Review created successfully.');
    }


    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            // 'user_id' => 'required',
            // 'booking_id' => 'required',
            'title' => 'required',
            'content' => 'required',
            'rating' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $review = Review::find($id);
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }
        $review->title = $input['title'];
        $review->content = $input['content'];
        $review->rating = $input['rating'];

        if ($review->save()) {
            return $this->sendResponse(new ReviewResource($review), 'Review updated successfully.');
        } else {
            return $this->sendError('Review update failed.');
        }
    }

    public function destroy($id)
    {
        $review = Review::find($id);
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $reply = $review->reply()->get();

        if ($review->delete()) {
            foreach ($reply as $item) {
                $item->delete();
            }
            return $this->sendResponse([], 'Review deleted successfully.');
        } else {
            return $this->sendError('Review delete failed.');
        }
    }

    public function restore($id)
    {
        $review = Review::onlyTrashed()->find($id);
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }
        // restore reply
        $reply = $review->reply()->onlyTrashed()->get();
        // echo $reply;
        if ($review->restore()) {
            foreach ($reply as $item) {
                $item->restore();
            }
            return $this->sendResponse([], 'Review restored successfully.');
        } else {
            return $this->sendError('Review restore failed.');
        }
    }

    public function getReviewByHotelId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::paginate(10);
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $hotel_id = $review->filter(function ($item) use ($id) {
            return $item->booking->hotel_id == $id;
        });

        if ($hotel_id->count() > 0) {
            return $this->sendResponse(ReviewResource::collection($hotel_id), 'Review retrieved successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function deleteByHotelId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::get();
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $hotel_id = $review->filter(function ($item) use ($id) {
            return $item->booking->hotel_id == $id;
        });

        $reply = $hotel_id->map(function ($item) {
            return $item->reply()->get();
        });
        // echo $reply;

        if ($hotel_id->count() > 0) {
            $hotel_id->each->delete();
            foreach ($reply as $item) {
                $item->each->delete();
            }
            return $this->sendResponse([], 'Review deleted successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function restoreByHotelId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::onlyTrashed()->get();
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $hotel_id = $review->filter(function ($item) use ($id) {
            return $item->booking->hotel_id == $id;
        });

        $reply = $hotel_id->map(function ($item) {
            return $item->reply()->onlyTrashed()->get();
        });

        if ($hotel_id->count() > 0) {
            $hotel_id->each->restore();
            foreach ($reply as $item) {
                $item->each->restore();
            }
            return $this->sendResponse([], 'Review restored successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function getReviewByBookingId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::where('booking_id', $id)->get();
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }
        if ($review->count() > 0) {
            return $this->sendResponse(ReviewResource::collection($review), 'Review retrieved successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function deleteByUserId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::get();
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $user_id = $review->filter(function ($item) use ($id) {
            return $item->user_id == $id;
        });
        
        // echo $user_id;

        $reply = $user_id->map(function ($item) {
            return $item->reply()->get();
        });
        // echo $reply;

        if ($user_id->count() > 0) {
            $user_id->each->delete();
            foreach ($reply as $item) {
                $item->each->delete();
            }
            return $this->sendResponse([], 'Review deleted successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function restoreByUserId($id)
    {
        // get hotel_id = $id in table booking
        $review = Review::onlyTrashed()->get();
        if (is_null($review)) {
            return $this->sendError('Review not found.');
        }

        $user_id = $review->filter(function ($item) use ($id) {
            return $item->user_id == $id;
        });

        $reply = $user_id->map(function ($item) {
            return $item->reply()->onlyTrashed()->get();
        });

        if ($user_id->count() > 0) {
            $user_id->each->restore();
            foreach ($reply as $item) {
                $item->each->restore();
            }
            return $this->sendResponse([], 'Review restored successfully.');
        } else {
            return $this->sendError('Review not found.');
        }
    }

    public function getReviewByUser($id)
    {
        $review = Review::where('user_id', $id)->paginate(10);

        $user = Auth::user();
        if ($user->id == $id) {
            if (is_null($review)) {
                return $this->sendError('Review not found.');
            }
            if ($review->count() > 0) {
                return $this->sendResponse(ReviewResource::collection($review), 'Review retrieved successfully.');
            } else {
                return $this->sendError('Review not found.');
            }
        } else {
            return $this->sendError('You are not authorized to access this page.');
        }
    }
}
