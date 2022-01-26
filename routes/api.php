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


// Регистрация
Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);

// Аутентификация
Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);

// Список аэропортов
Route::get('airport', [\App\Http\Controllers\AirportController::class, 'search']);

// Оформление бронирования
Route::post('booking', [\App\Http\Controllers\BookingController::class, 'create']);

// Получение занятых мест в самолете
Route::get('booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'findSeatByCode']);

// Выбор места в салоне
Route::patch('booking/{code}/seat', [\App\Http\Controllers\BookingController::class, 'changeSeat']);

// Получение информации о пользователе
Route::get('user', [\App\Http\Controllers\UserController::class, 'search']);

// Поиск рейсов
Route::get('flight', [\App\Http\Controllers\FlightController::class, 'search']);

// Информация о бронировании
Route::get('booking/{code}', [\App\Http\Controllers\BookingController::class, 'search']);

// Получение своих бронирований
Route::get('user/booking', [\App\Http\Controllers\BookingController::class, 'findByToken']);
