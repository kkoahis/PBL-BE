<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\SendGridHandler;
use PhpParser\Node\Expr\FuncCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

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

        $reviewOfHotel = DB::table('review')
            ->join('booking', 'review.booking_id', '=', 'booking.id')
            ->join('users', 'booking.user_id', '=', 'users.id')
            ->select('review.*', 'users.name')
            ->where('booking.hotel_id', '=', $id)
            ->paginate(20);

        $hotel->review = $reviewOfHotel;

        // return $this->sendResponse(new HotelResource($hotel), 'Hotel retrieved successfully.');
        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => new HotelResource($hotel),
            'reviews' =>  $reviewOfHotel
        ]
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role != 'hotel') {
            return $this->sendError('You are not authorized to create hotel.');
        }

        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'address' => 'required',
            'hotline' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'email' => 'required|email',
            'description',
            'room_total' => 'required | numeric',
            'parking_slot' => 'required | numeric',
            'bathrooms' => 'required | numeric',
            // create_by has to be user admin in table users
            // 'created_by' => 'required',
            'amenities' => 'required',
            'Safety_Hygiene' => 'required',
            'check_in|date',
            'check_out|date',
            'guests | numeric',
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
            'email' => 'email | email',
            'description',
            'room_total | numeric',
            'parking_slot | numeric',
            'bathrooms | numeric',
            'amenities',
            'Safety_Hygiene',
            // 'check_in | date',
            // 'check_out | date',
            'guests | numeric',
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
            // $hotel->check_in = $input['check_in'];
            // $hotel->check_out = $input['check_out'];
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

            $categoryImage = $hotel->category->map(function ($category) {
                return $category->categoryImage;
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
                foreach ($categoryImage as $ci) {
                    foreach ($ci as $c) {
                        $c->delete();
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
            $categoryImage = $hotel->category()->onlyTrashed()->get()->map(function ($category) {
                return $category->categoryImage()->onlyTrashed()->get();
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
                foreach ($categoryImage as $ci) {
                    foreach ($ci as $c) {
                        $c->restore();
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

    public function getHotelNearby()
    {
        // get 7 hotel nearby City = Da Nang
        $hotel = Hotel::orderBy('id', 'asc')->where('city', 'like', '%' . 'Da Nang' . '%')->with('hotelImage')->limit(7)->get();

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

    public function getHotelTopBooked()
    {
        // get 7 hotel top booked
        $hotelBooked = Booking::select('hotel_id', DB::raw('count(*) as total'))->groupBy('hotel_id')->orderBy('total', 'desc')->limit(6)->get();

        $hotel = array();
        foreach ($hotelBooked as $key => $value) {
            $hotel[$key] = Hotel::where('id', $value->hotel_id)->with('hotelImage')->first();
        }

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

    public function getCityOfHotel()
    {
        // get 5 city of hotel don't group by
        $hotel = Hotel::select('city')->distinct()->limit(5)->get();

        $item = array();
        foreach ($hotel as $key => $value) {
            $item[$key] = $value->city;
        }

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }

        return response()->json(([
            'success' => true,
            'message' => 'Hotel retrieved successfully.',
            'data' => $item,
        ]
        ));
    }

    public function getHotelByPriceAndCity(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'city' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        if ($input['min_price'] == 0 && $input['max_price'] == 0) {

            return $this->sendError('Min price and max price must be greater than 0.');
        }

        $hotel = Hotel::orderBy('price', 'asc')->whereBetween('price', [$input['min_price'], $input['max_price']])->where('city', 'like', '%' . $input['city'] . '%')->with('hotelImage')->paginate(20);

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

    public function getHotelLatest()
    {
        // get 7 hotel latest
        $hotel = Hotel::orderBy('id', 'desc')->with('hotelImage')->limit(7)->get();

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

    public function getHotelByHost(Request $request){
        $token = PersonalAccessToken::findToken($request->bearerToken());

        $user = User::find($token->tokenable_id);

        $hotel = Hotel::where('created_by', $user->id)->with('hotelImage')->paginate(20);

        if (is_null($hotel)) {
            return $this->sendError('Hotel not found.');
        }
        if ($user->role == 'hotel') {
            return response()->json(([
                'success' => true,
                'message' => 'Hotel retrieved successfully.',
                'data' => $hotel,
            ]
            ));
        } else {
            return $this->sendError('You are not permission.');
        }
    }
}
