<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HotelController;
use App\Http\Controllers\API\HotelImageController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\BookingDetailController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ReplyController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryImageController;
use App\Http\Controllers\API\NewPasswordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(NewPasswordController::class)->group(function () {
    Route::post('/forgot-password', [NewPasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [NewPasswordController::class, 'reset']);
});

Route::controller(UserController::class)->group(function () {
    Route::post('/me', [UserController::class, 'Me']);
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});

// Route updateRole for admin by email input field
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('users')->group(function () 
{
    Route::put('/role/{email}', [UserController::class, 'updateRole']);
    Route::get('/allUser', [UserController::class, 'getAllUser']);
    Route::get('/profile/{id}', [UserController::class, 'getProfile']);
    Route::put('/editProfile/{id}', [UserController::class, 'editProfile']);
});

Route::prefix('hotels')->group(function () {
    Route::get('/latest', [HotelController::class, 'getHotelLatest']);
    Route::get('/safety-hygiene', [HotelController::class, 'getHotelBySafetyHygiene']);
    Route::get('/amenities', [HotelController::class, 'getHotelByAmenities']);
    Route::get('/rating', [HotelController::class, 'getHotelByRating']);
    Route::get('/guests', [HotelController::class, 'getHotelByGuests']);
    Route::get('/prices&city', [HotelController::class, 'getHotelByPriceAndCity']);
    Route::get('/prices', [HotelController::class, 'getHotelByPrice']);
    Route::get('/nearby', [HotelController::class, 'getHotelNearby']);
    Route::get('/top-booked', [HotelController::class, 'getHotelTopBooked']);
    Route::get('/city', [HotelController::class, 'getCityOfHotel']);
    Route::get('/', [HotelController::class, 'index']);
    Route::get('/{id}', [HotelController::class, 'show']);
    Route::get('/name/{name}', [HotelController::class, 'getHotelByName']);
    Route::get('/address/{address}', [HotelController::class, 'getHotelByAddress']);
    Route::get('/city/{city}', [HotelController::class, 'getHotelByCity']);
    Route::get('/nation/{nation}', [HotelController::class, 'getHotelByNation']);
});

// Hotels API 
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('hotels')->group(function () {
    Route::post('/', [HotelController::class, 'store']);
    Route::put('/{id}', [HotelController::class, 'update']);
    Route::delete('/{id}', [HotelController::class, 'destroy']);

    Route::put('/restore/{id}', [HotelController::class, 'restore']);
});

Route::prefix('hotel-images')->group(function () {
    Route::get('/', [HotelImageController::class, 'index']);
    Route::get('/{id}', [HotelImageController::class, 'show']);
    Route::get('/hotel/{id}', [HotelImageController::class, 'getImageByHotelId']);
});

// hotel images API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('hotel-images')->group(function () {
    Route::post('/', [HotelImageController::class, 'store']);
    Route::put('/{id}', [HotelImageController::class, 'update']);
    Route::delete('/{id}', [HotelImageController::class, 'destroy']);

    Route::delete('/hotel/{id}', [HotelImageController::class, 'deleteImageByHotelId']);
    Route::put('/hotel/{id}', [HotelImageController::class, 'restoreByHotelId']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'getCategoryByCheckInCheckOutHotel']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::get('/hotel/{id}', [CategoryController::class, 'getCategoryByHotelId']);
    Route::get('/price/{id}', [CategoryController::class, 'getPriceByCategoryId']);
});

// Categories API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('categories')->group(function () {
    Route::post('/', [CategoryController::class, 'store']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);

    Route::put('/restore/{id}', [CategoryController::class, 'restore']);
    Route::delete('/hotel/{id}', [CategoryController::class, 'deleteCategoryByHotelId']);
    Route::put('/hotel/{id}', [CategoryController::class, 'restoreByHotelId']);
    Route::put('/price/{id}', [CategoryController::class, 'updatePriceByCategoryId']);
});

Route::prefix('category-images')->group(function () {
    Route::get('/', [CategoryImageController::class, 'index']);
    Route::get('/{id}', [CategoryImageController::class, 'show']);
    Route::get('/category/{id}', [CategoryImageController::class, 'getImageBycategoryId']);
});

// Category Image API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('category-images')->group(function () {
    Route::post('/', [CategoryImageController::class, 'store']);
    Route::put('/{id}', [CategoryImageController::class, 'update']);
    Route::delete('/{id}', [CategoryImageController::class, 'destroy']);

    Route::delete('/category/{id}', [CategoryImageController::class, 'deleteByCategoryId']);
    Route::put('/category/{id}', [CategoryImageController::class, 'restoreBycategoryId']);
});

Route::prefix('rooms')->group(function () {
    // CountRoomByCategoryId
    Route::get('count/hotel/{id}', [RoomController::class, 'countRoomByCategoryId']);
    Route::get('/', [RoomController::class, 'index']);
    Route::get('/{id}', [RoomController::class, 'show']);
});

// Room API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('rooms')->group(function () {
    Route::post('/count', [RoomController::class, 'storeWithCount']);
    Route::post('/', [RoomController::class, 'store']);
    Route::put('/{id}', [RoomController::class, 'update']);
    Route::delete('/{id}', [RoomController::class, 'destroy']);

    Route::put('/restore/{id}', [RoomController::class, 'restore']);
    Route::put('/category/{id}', [RoomController::class, 'restoreByCategoryId']);
    Route::put('/hotel/{id}', [RoomController::class, 'restoreByHotelId']);
});

// Booking API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('bookings')->group(function () {
    // Route::get('', [BookingController::class, 'index']);
    // Route::get('/{id}', [BookingController::class, 'show']);
    // Route::put('/{id}', [BookingController::class, 'update']);
    // Route::delete('/{id}', [BookingController::class, 'destroy']);

    Route::get('/hotel/{id}', [BookingController::class, 'getBookingByHotelId']);
    Route::get('/hotel/past/{id}', [BookingController::class, 'getBookingByHotelIdPast']);
    // acceptBooking
    Route::put('/accept/{id}', [BookingController::class, 'acceptBooking']);
    // cancelBooking
    Route::put('/reject/{id}', [BookingController::class, 'rejectBooking']);
});


Route::middleware(['auth:sanctum', 'verified', 'role:user'])->prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'store']);
    Route::get('/user/{id}', [BookingController::class, 'getBookingByUserId']);
    Route::get('/user/past/{id}', [BookingController::class, 'getBookingByUserIdPast']);
});

// Booking Detail API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel'])->prefix('booking-details')->group(function () {
    Route::get('', [BookingDetailController::class, 'index']);
    Route::get('/{id}', [BookingDetailController::class, 'show']);
    Route::post('/', [BookingDetailController::class, 'store']);
    Route::put('/{id}', [BookingDetailController::class, 'update']);
    Route::delete('/{id}', [BookingDetailController::class, 'destroy']);

    Route::put('/restore/{id}', [BookingDetailController::class, 'restore']);
    Route::get('/booking/{id}', [BookingDetailController::class, 'getBookingDetailByBookingId']);
    Route::get('/room/{id}', [BookingDetailController::class, 'getBookingDetailByRoomId']);
});


// Payment API
Route::middleware(['auth:sanctum', 'verified', 'role:user'])->prefix('payments')->group(
    function () {
        // Route::get('', [PaymentController::class, 'index']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::put('/', [PaymentController::class, 'update']);
        Route::delete('/{id}', [PaymentController::class, 'destroy']);

        Route::get('/booking/{id}', [PaymentController::class, 'getPaymentByBookingId']);
        Route::delete('/booking/{id}', [PaymentController::class, 'deleteByBookingId']);
    }
);

Route::prefix('reviews')->group(
    function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('/{id}', [ReviewController::class, 'show']);
        Route::get('/hotel/{id}', [ReviewController::class, 'getReviewByHotelId']);
        Route::get('/booking/{id}', [ReviewController::class, 'getReviewByBookingId']);
        // Route::get('/user/{id}', [ReviewController::class, 'getReviewByUserId']);
    }
);

// Review API
Route::middleware(['auth:sanctum', 'verified', 'role:user'])->prefix('reviews')->group(function () {
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::get('reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/', [ReviewController::class, 'store']);
    Route::put('/{id}', [ReviewController::class, 'update']);
    Route::delete('/{id}', [ReviewController::class, 'destroy']);

    Route::get('reviews/hotel/{id}', [ReviewController::class, 'getReviewByHotelId']);
    Route::delete('/hotel/{id}', [ReviewController::class, 'deleteByHotelId']);
    Route::put('/hotel/{id}', [ReviewController::class, 'restoreByHotelId']);

    Route::get('reviews/booking/{id}', [ReviewController::class, 'getReviewByBookingId']);
    Route::put('/restore/{id}', [ReviewController::class, 'restore']);
    Route::put('/hotel/{id}', [ReviewController::class, 'restoreByHotelId']);

    // Route::get('/user/{id}', [ReviewController::class, 'getReviewByUserId']);
    Route::put('/user/{id}', [ReviewController::class, 'restoreByUserId']);
    Route::delete('/user/{id}', [ReviewController::class, 'deleteByUserId']);
});


Route::prefix('replies')->group(
    function () {
        Route::get('/', [ReplyController::class, 'index']);
        Route::get('/{id}', [ReplyController::class, 'show']);
        Route::get('/review/{id}', [ReplyController::class, 'getByReviewId']);
    }
);

// Reply API
Route::middleware(['auth:sanctum', 'verified', 'role:hotel,admin'])->prefix('replies')->group(function () {
    Route::get('replies', [ReplyController::class, 'index']);
    Route::get('replies/{id}', [ReplyController::class, 'show']);
    Route::post('/', [ReplyController::class, 'store']);
    Route::put('/{id}', [ReplyController::class, 'update']);
    Route::delete('/{id}', [ReplyController::class, 'destroy']);

    Route::put('/restore/{id}', [ReplyController::class, 'restore']);
    Route::get('replies/review/{id}', [ReplyController::class, 'getByReviewId']);
    Route::delete('/review/{id}', [ReplyController::class, 'deleteByReviewId']);
    Route::put('/review/{id}', [ReplyController::class, 'restoreByReviewId']);
});
