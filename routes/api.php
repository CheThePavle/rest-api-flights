<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

Route::get('/airport', [\App\Http\Controllers\AirportController::class, 'search']);

Route::get('/user', [\App\Http\Controllers\UserController::class, 'findByToken']);

Route::get('/booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'findSeatByCode']);

Route::patch('/booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'selectSeat']);

Route::post('/booking', [\App\Http\Controllers\BookingController::class, 'booking']);
