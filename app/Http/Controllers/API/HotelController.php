<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\SendGridHandler;
use PhpParser\Node\Expr\FuncCall;
use Carbon\Carbon;

use function PHPUnit\Framework\isNull;

class HotelController extends BaseController
{
    //
    public function index()
    {
        $hotel = Hotel::paginate(20);
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function show($id)
    {
        $hotel = Hotel::find($id);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }

        return $this->sendResponse(new HotelResource($hotel), 'Hotel retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'address' => 'required',
            'hotline' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'email' => 'required|email',
            'description',
            'room_total' => 'required',
            'parking_slot' => 'required',
            'bathrooms' => 'required',
            // create_by has to be user admin in table users
            // 'created_by' => 'required',
            'amenities' => 'required',
            'Safety_Hygiene' => 'required',
            'check_in|date',
            'check_out|date',
            'guests',
            'city' => 'required',
            'nation' => 'required',
            'price',
            // 'rating',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $hotel = new Hotel();

        $hotel->name = $input['name'];
        $hotel->address = $input['address'];
        $hotel->hotline = $input['hotline'];
        if (Hotel::where('hotline', $input['hotline'])->exists()) {
            return $this->sendError('Hotline already exists.');
        }

        $hotel->email = $input['email'];
        if (Hotel::where('email', $input['email'])->exists()) {
            return $this->sendError('Email already exists.');
        }

        $hotel->description = $input['description'];
        $hotel->room_total = $input['room_total'];
        $hotel->parking_slot = $input['parking_slot'];
        $hotel->bathrooms = $input['bathrooms'];
        $hotel->amenities = $input['amenities'];
        $hotel->Safety_Hygiene = $input['Safety_Hygiene'];

        // check in is timenow 
        $hotel->check_in = Carbon::now();
        $hotel->check_out = $input['check_out'];
        $hotel->guests = $input['guests'];
        $hotel->city = $input['city'];
        $hotel->nation = $input['nation'];
        $hotel->price = $input['price'];

        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to create hotel.');
        }
        $hotel->created_by = $user->id;

        // dd($hotel->created_by);

        if ($hotel->save()) {
            return response()->json(([
                    'success' => true,
                    'message' => 'Hotel created successfully.',
                    'data' => $hotel,
                ])
            );
        } else {
            return $this->sendError('Hotel not created.');
        };
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name',
            'address',
            // must be in correct phone format
            'hotline' => 'regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            // must be in correct email format
            'email' => 'email',
            'description',
            'room_total',
            'parking_slot',
            'bathrooms',
            'amenities',
            'Safety_Hygiene',
            'check_in',
            'check_out',
            'guests',
            'city',
            'nation',
            'price',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $user = Auth::user();
        $hotel = Hotel::find($id);
        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }

        if ($user->id != $hotel->created_by) {
            return $this->sendError('You are not authorized to update this hotel.');
        } else {

            $hotel->name = $input['name'];
            $hotel->address = $input['address'];
            $hotel->hotline = $input['hotline'];
            if (Hotel::where('hotline', $input['hotline'])->where('id', '!=', $id)->exists()) {
                return $this->sendError('Hotline already exists.');
            }

            $hotel->email = $input['email'];
            if (Hotel::where('email', $input['email'])->where('id', '!=', $id)->exists()) {
                return $this->sendError('Email already exists.');
            }


            $hotel->description = $input['description'];
            $hotel->room_total = $input['room_total'];
            $hotel->parking_slot = $input['parking_slot'];
            $hotel->bathrooms = $input['bathrooms'];
            $hotel->amenities = $input['amenities'];
            $hotel->Safety_Hygiene = $input['Safety_Hygiene'];
            $hotel->check_in = $input['check_in'];
            $hotel->check_out = $input['check_out'];
            $hotel->guests = $input['guests'];
            $hotel->city = $input['city'];
            $hotel->nation = $input['nation'];
            $hotel->price = $input['price'];

            if ($hotel->save()) {
                return response()->json(([
                        'success' => true,
                        'message' => 'Hotel updated successfully.',
                        'data' => $hotel,
                    ])
                );
            } else {
                return $this->sendError('Hotel not updated.');
            }
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $hotel = Hotel::find($id);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }

        if ($user->id != $hotel->created_by) {
            return $this->sendError('You are not authorized to delete this hotel.');
        } else {
            $category = $hotel->category;
            $hotelImage = $hotel->hotelImage;
            $room = $hotel->category->map(function ($category) {
                return $category->room;
            });
            $roomImage = $hotel->category->map(function ($category) {
                return $category->room->map(function ($room) {
                    return $room->roomImage;
                });
            });
            $booking = $hotel->booking;

            // only get review != null
            $review = $hotel->booking->map(function ($booking) {
                return $booking->review;
            })->filter(function ($review) {
                return !is_null($review);
            });

            // only get reply != null
            $reply = $review->map(function ($review) {
                return $review->reply;
            })->filter(function ($reply) {
                return !is_null($reply);
            });

            // echo $reply;
            // echo $room;  
            // echo $roomImage;
            // echo $review;

            // if hotel delete, category, room and hotel image will update deleted_at
            if ($hotel->delete()) {
                foreach ($category as $cat) {
                    $cat->delete();
                }
                foreach ($room as $r) {
                    foreach ($r as $ro) {
                        $ro->delete();
                    }
                }
                foreach ($hotelImage as $hi) {
                    $hi->delete();
                }
                foreach ($roomImage as $ri) {
                    foreach ($ri as $r) {
                        foreach ($r as $ro) {
                            $ro->delete();
                        }
                    }
                }
                foreach ($booking as $b) {
                    $b->delete();
                }

                // delete review, if review has null value, it will not delete
                foreach ($review as $r) {
                    $r->delete();
                }

                // delete reply, if reply has null value, it will not delete
                foreach ($reply as $rp) {
                    foreach ($rp as $r) {
                        $r->delete();
                    }
                }
            } else {
                return $this->sendError('Hotel not deleted.');
            }
            return $this->sendResponse([], 'Hotel deleted successfully.');
        }
    }


    public function restore($id)
    {
        $user = Auth::user();
        // if hotel restore, category, room, room image and hotel image will update deleted_at
        $hotel = Hotel::onlyTrashed()->find($id);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }

        if ($user->id != $hotel->created_by) {
            return $this->sendError('You are not authorized to restore this hotel.');
        } else {
            $category = $hotel->category()->onlyTrashed()->get();
            $hotelImage = $hotel->hotelImage()->onlyTrashed()->get();
            $room = $hotel->category()->onlyTrashed()->get()->map(function ($category) {
                return $category->room()->onlyTrashed()->get();
            });
            $roomImage = $hotel->category()->onlyTrashed()->get()->map(function ($category) {
                return $category->room()->onlyTrashed()->get()->map(function ($room) {
                    return $room->roomImage()->onlyTrashed()->get();
                });
            });
            $booking = $hotel->booking()->onlyTrashed()->get();
            $review = $hotel->booking()->onlyTrashed()->get()->map(function ($booking) {
                return $booking->review()->onlyTrashed()->get();
            });
            $reply = $hotel->booking()->onlyTrashed()->get()->map(function ($booking) {
                return $booking->review()->onlyTrashed()->get()->map(function ($review) {
                    return $review->reply()->onlyTrashed()->get();
                });
            });

            // echo $category . "<br>";
            // echo $hotelImage . "<br>";
            // echo $room . "<br>";
            // echo $roomImage . "<br>";

            // restore hotel
            if ($hotel->restore()) {
                // restore category
                foreach ($category as $cat) {
                    $cat->restore();
                }
                // restore room
                foreach ($room as $r) {
                    foreach ($r as $ro) {
                        $ro->restore();
                    }
                }
                // restore hotel image
                foreach ($hotelImage as $hi) {
                    $hi->restore();
                }
                // restore room image
                foreach ($roomImage as $ri) {
                    foreach ($ri as $r) {
                        foreach ($r as $ro) {
                            $ro->restore();
                        }
                    }
                }

                // restore booking
                foreach ($booking as $b) {
                    $b->restore();
                }
                // restore review
                foreach ($review as $r) {
                    foreach ($r as $ro) {
                        $ro->restore();
                    }
                }
                // restore reply
                foreach ($reply as $rp) {
                    foreach ($rp as $r) {
                        foreach ($r as $ro) {
                            $ro->restore();
                        }
                    }
                }

                return $this->sendResponse([], 'Hotel restored successfully.');
            } else {
                return $this->sendError('Hotel not restored.');
            }
        }
    }

    public function getHotelByName($name)
    {
        $hotel = Hotel::orderBy('name', 'asc')->where('name', 'like', '%' . $name . '%')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByAddress($address)
    {
        $hotel = Hotel::orderBy('address', 'asc')->where('address', 'like', '%' . $address . '%')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByCity($city)
    {
        $hotel = Hotel::orderBy('city', 'asc')->where('city', 'like', '%' . $city . '%')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByNation($nation)
    {
        $hotel = Hotel::orderBy('nation', 'asc')->where('nation', 'like', '%' . $nation . '%')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByPrice(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($input['min_price'] > $input['max_price']) {
            return $this->sendError('Min price must be less than max price.');
        }

        if ($input['min_price'] < 0 || $input['max_price'] < 0) {
            return $this->sendError('Price must be greater than 0.');
        }

        if ($input['min_price'] == $input['max_price']) {
            return $this->sendError('Min price must be different from max price.');
        }

        if ($input['min_price'] == 0 && $input['max_price'] == 0) {
            return $this->sendError('Min price and max price must be greater than 0.');
        }

        $hotel = Hotel::orderBy('price', 'asc')->whereBetween('price', [$input['min_price'], $input['max_price']])->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByCheckInAndCheckOut(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'check_in' => 'required|date',
            'check_out' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($input['check_in'] > $input['check_out']) {
            return $this->sendError('Check in must be less than check out.');
        }

        $hotel = Hotel::orderBy('check_in', 'asc')->whereBetween('check_in', [$input['check_in'], $input['check_out']])->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByGuests(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'guests' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($input['guests'] < 0) {
            return $this->sendError('Guests must be greater than 0.');
        }

        // get hotel with guests <= get column guests
        $hotel = Hotel::orderBy('guests', 'asc')->where('guests', '>=', $input['guests'])->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByRating(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'rating' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($input['rating'] < 0) {
            return $this->sendError('Rating must be greater than 0.');
        }

        if ($input['rating'] > 5) {
            return $this->sendError('Rating must be less than 5.');
        }

        // get hotel with rating >= get column rating
        $hotel = Hotel::orderBy('rating', 'asc')->where('rating', '>=', $input['rating'])->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelByAmenities(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'amenities' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // get hotel with amenities like get column amenities
        $hotel = Hotel::orderBy('amenities', 'asc')->where('amenities', 'like', '%' . $input['amenities'] . '%')->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }

    public function getHotelBySafetyHygiene(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'Safety_Hygiene' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $hotel = Hotel::orderBy('Safety_Hygiene', 'asc')->where('Safety_Hygiene', 'like', '%' . $input['Safety_Hygiene'] . '%')->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $hotel,
        ]
        ));
    }
}
