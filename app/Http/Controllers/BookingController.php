<?php

namespace App\Http\Controllers;

use App\Http\Resources\PassengerResource;
use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Str;
use DB;

class BookingController extends Controller
{
    public function create(Request $request) {
        $rules = [
            'flight_from'=>'required',
            'flight_from.id'=>'required|exists:flights,id',
            'flight_from.date'=>'required|date_format:Y-m-d',
            'flight_back.id'=>'required_with:flight_back||exists:flights,id',
            'flight_back.date'=>'required_with:flight_back|date_format:Y-m-d',
            'passengers'=>'required|array|between:1,8',
            'passengers.*.first_name'=>'required',
            'passengers.*.last_name'=>'required',
            'passengers.*.birth_date'=>'required|date_format:Y-m-d',
            'passengers.*.document_number'=>'required|numeric|digits:10',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Validation error',
                    'errors'=>$validator->errors()
                ]
            ], 422);
        }

        $code = Str::upper(Str::random(5));

        $idBooking = NULL;
        if ($request->flight_back) {
            $idBooking = Booking::create([
                'flight_from'=>$request->flight_from['id'],
                'date_from'=>$request->flight_from['date'],
                'flight_back'=>$request->flight_back['id'],
                'date_back'=>$request->flight_back['date'],
                'code'=>$code
            ])->id;
        } else {
            $idBooking = Booking::create([
                'flight_from'=>$request->flight_from['id'],
                'date_from'=>$request->flight_from['date'],
                'code'=>$code
            ])->id;
        }

        $passengers = $request->passengers;
        foreach ($passengers as &$el) {
            $el['booking_id'] = $idBooking;
            $el['created_at'] = date('Y:m:d H:i:s');
            $el['updated_at'] = date('Y:m:d H:i:s');
        }

        Passenger::insert($passengers);

        return response()->json([
            'data'=>[
                'code'=>$code
            ]
        ], 201);
    }

    public function findSeatByCode($code) {
        $check = Booking::where('code', '=', $code)->first();
        if (!$check) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Такого кода не существует'
                ]
            ], 422);
        }

        $bookingInfo = DB::select("SELECT p.id, p.place_from, p.place_back FROM bookings as b
                                    JOIN passengers as p ON p.booking_id = b.id
                                    WHERE b.date_from = ? AND b.date_back = ?;", [$check->date_from, $check->date_back]);

        $occupied_from = [];
        foreach ($bookingInfo as $el) {
            $place_from = $el->place_from;
            if ($place_from) {
                array_push($occupied_from, [
                    'passenger_id'=>$el->id,
                    'place'=>$place_from,
                ]);
            }
        }

        $occupied_back = [];
        foreach ($bookingInfo as $el) {
            $place_back = $el->place_back;
            if ($place_back) {
                array_push($occupied_back, [
                    'passenger_id'=>$el->id,
                    'place'=>$place_back,
                ]);
            }
        }

        return response()->json([
            'data'=>[
                'occupied_from'=>$occupied_from,
                'occupied_back'=>$occupied_back
            ]
        ]);
    }

    public function changeSeat($code, Request $request) {
        $rules = [
            'passenger'=>'required',
            'seat'=>'required',
            'type'=>'required|in:from,back',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Validation error',
                    'errors'=>$validator->errors()
                ]
            ], 422);
        }

        $getPassenger = Passenger::where('id', '=', $request->passenger)->first();

        if (!$getPassenger){
            return response()->json([
                'error'=>[
                    'code'=>403,
                    'message'=>'Passenger does not apply to booking'
                ]
            ], 403);
        }

        $getBookingId = $getPassenger->booking_id;

        $getBooking = Booking::where('id', '=', $getBookingId)->first();

        $place = $request->type == 'from' ? 'place_from' : 'place_back';
        $getSeats = DB::select("SELECT * FROM bookings as b JOIN passengers as p ON p.booking_id = b.id
                                WHERE b.date_from = ? AND b.date_back = ? AND p.$place = ?;", [$getBooking->date_from, $getBooking->date_back, $request->seat]);

        if (count($getSeats) > 0) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Seat is occupied'
                ]
            ], 422);
        }

        Passenger::where('id', '=', $request->passenger)
                    ->update([$place=>$request->seat]);

        return response()->json([
            'data'=>[
                'id'=>$getPassenger->id,
                'first_name'=>$getPassenger->first_name,
                'last_name'=>$getPassenger->last_name,
                'birth_date'=>$getPassenger->birth_date,
                'document_number'=>$getPassenger->document_number,
                'place_from'=>$place == 'place_from' ? $request->seat : $getPassenger->place_from,
                'place_back'=>$place == 'place_back' ? $request->seat : $getPassenger->place_from
            ]
        ]);
    }

    public function search($code) {
        $getBooking = Booking::where('code', '=', $code)->first();

        if (!$getBooking) {
            return response()->json([
                'data'=>[]
            ]);
        }

        $getBookingId = $getBooking->id;

        $getFlights = DB::select("SELECT f.id as 'flight_id', f.flight_code, f.cost,
                                    fromA.city as 'fromCity', fromA.name as 'fromAirport', fromA.iata as 'fromIata', f.time_from as 'fromTime',
                                    toA.city as 'toCity', toA.name as 'toAirport', toA.iata as 'toIata', f.time_to as 'toTime',
                                    b.date_from, b.date_back
                                    FROM airports as fromA
                                    JOIN (flights as f JOIN airports as toA ON toA.id = f.to_id)
                                    ON fromA.id = f.from_id
                                    JOIN bookings as b ON b.flight_from = f.id OR b.flight_back = f.id
                                    WHERE b.code = ? AND b.id = ?;", [$code, $getBookingId]);

        $jsonFlights = [];
        $forDate = true;
        $sumCost = 0;
        foreach ($getFlights as $el) {
            $sumCost = $sumCost + $el->cost;
            array_push($jsonFlights, [
                'flight_id'=>$el->flight_id,
                'flight_code'=>$el->flight_code,
                'from'=>[
                    'city'=>$el->fromCity,
                    'airport'=>$el->fromAirport,
                    'iata'=>$el->fromIata,
                    'date'=>$forDate ? $el->date_from : $el->date_back,
                    'time'=>date('H:i', strtotime($el->fromTime))
                ],
                'to'=>[
                    'city'=>$el->toCity,
                    'airport'=>$el->toAirport,
                    'iata'=>$el->toIata,
                    'date'=>$forDate ? $el->date_from : $el->date_back,
                    'time'=>date('H:i', strtotime($el->toTime))
                ],
                'cost'=>$el->cost,
                'availability'=>156
            ]);
            $forDate = false;
        }

        $passengers = Passenger::where('booking_id', '=', $getBookingId)->get();
        $count = count($passengers);
        $sumCost = $count > 0 ? $count * $sumCost : $sumCost;

        return response()->json([
            'data'=>[
                'code'=>$code,
                'cost'=>$sumCost,
                'flights'=>$jsonFlights,
                'passengers'=>PassengerResource::collection($passengers)
            ]
        ]);
    }

    public function findByToken(Request $request) {
        $token = $request->bearerToken();

        $user = User::where('api_token', '=', $token)->first();
        if (!$user) {
            return response()->json([
                'error'=>[
                    'code'=>401,
                    'message'=>'Unauthorized'
                ]
            ], 401);
        }

        $getBookings = Passenger::where('document_number', '=', $user->document_number)->get();

        if (count($getBookings) == 0) {
            return response()->json([
                'data'=>[
                    'items'=>[]
                ]
            ]);
        }

        $items = [];
        foreach ($getBookings as $el) {
            $getFlights = DB::select("SELECT f.id as 'flight_id', f.flight_code, f.cost,
                                    fromA.city as 'fromCity', fromA.name as 'fromAirport', fromA.iata as 'fromIata', f.time_from as 'fromTime',
                                    toA.city as 'toCity', toA.name as 'toAirport', toA.iata as 'toIata', f.time_to as 'toTime',
                                    b.date_from, b.date_back, b.code
                                    FROM airports as fromA
                                    JOIN (flights as f JOIN airports as toA ON toA.id = f.to_id)
                                    ON fromA.id = f.from_id
                                    JOIN bookings as b ON b.flight_from = f.id OR b.flight_back = f.id
                                    WHERE b.id = ?;", [$el->booking_id]);

            if (count($getFlights) > 0) {
                $jsonFlights = [];
                $forDate = true;
                $sumCost = 0;
                foreach ($getFlights as $item) {
                    $sumCost = $sumCost + $item->cost;
                    array_push($jsonFlights, [
                        'flight_id'=>$item->flight_id,
                        'flight_code'=>$item->flight_code,
                        'from'=>[
                            'city'=>$item->fromCity,
                            'airport'=>$item->fromAirport,
                            'iata'=>$item->fromIata,
                            'date'=>$forDate ? $item->date_from : $item->date_back,
                            'time'=>date('H:i', strtotime($item->fromTime))
                        ],
                        'to'=>[
                            'city'=>$item->toCity,
                            'airport'=>$item->toAirport,
                            'iata'=>$item->toIata,
                            'date'=>$forDate ? $item->date_from : $item->date_back,
                            'time'=>date('H:i', strtotime($item->toTime))
                        ],
                        'cost'=>$item->cost,
                        'availability'=>156
                    ]);
                    $forDate = false;
                }
                $passengers = Passenger::where('booking_id', '=', $el->booking_id)->get();
                $count = count($passengers);
                $sumCost = $count > 0 ? $count * $sumCost : $sumCost;

                array_push($items, [
                    'code'=>$getFlights[0]->code,
                    'cost'=>$sumCost,
                    'flights'=>$jsonFlights,
                    'passengers'=>PassengerResource::collection($passengers)
                ]);
            }
        }

        return response()->json([
            'data'=>[
                'items'=>$items
            ]
        ]);
    }
}
