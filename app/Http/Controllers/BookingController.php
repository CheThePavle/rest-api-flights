<?php

namespace App\Http\Controllers;

use App\Http\Resources\PassengerResource;
use App\Models\Booking;
use App\Models\Passenger;
use App\Models\User;
use Illuminate\Http\Request;
use DB;
use Validator;
use Str;

class BookingController extends Controller
{
    public function findSeatByCode($code) {
        $result = DB::select('SELECT p.id, p.place_from, p.place_back
                                FROM bookings as b JOIN passengers as p ON p.booking_id = b.id
                                WHERE b.code = ?', [$code]);

        $occupied_from = [];
        foreach ($result as $el) {
            $place_from = $el->place_from;
            if ($place_from) {
                array_push($occupied_from, [
                    'passenger_id'=>$el->id,
                    'place'=>$place_from
                ]);
            }
        }

        $occupied_back = [];
        foreach ($result as $el) {
            $place_back = $el->place_back;
            if ($place_back) {
                array_push($occupied_back, [
                    'passenger_id'=>$el->id,
                    'place'=>$place_back
                ]);
            }
        }

        return response()->json([
            'data' => [
                'occupied_from'=>$occupied_from,
                'occupied_back'=>$occupied_back
            ]
        ]);
    }

    // протестировать
    public function selectSeat(Request $request) {
        $data = $request->all();

        $rules = [
            'passenger'=>'required',
            'seat'=>'required',
            'type'=>'required|in:from,back'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails())
        {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Validation error',
                    'errors'=>$validator->errors()
                ]
            ], 422);
        }

        $checkPassenger = Passenger::where('id', $request->passenger)->first();
        if (!$checkPassenger) {
            return response()->json([
                'error'=>[
                    'code'=>403,
                    'message'=>'Passenger does not apply to booking'
                ]
            ], 403);
        }

        $place = $request->type == 'from' ? 'place_from' : 'place_back';
        $checkBooking = Passenger::
                        where('booking_id', '=', $checkPassenger->booking_id)
                        ->where($place, '=', $request->seat)
                        ->get();

        if (count($checkBooking) > 0) {
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
                'id'=>$checkPassenger->id,
                'first_name'=>$checkPassenger->first_name,
                'last_name'=>$checkPassenger->last_name,
                'birth_date'=>$checkPassenger->birth_date,
                'document_number'=>$checkPassenger->document_number,
                'place_from'=>$place == 'place_from' ? $request->seat : $checkPassenger->place_from,
                'place_back'=>$place == 'place_back' ? $request->seat : $checkPassenger->place_back,
            ]
        ]);
    }

    public function booking(Request $request) {
        $rules = [
            'flight_from'=>'required',
            'flight_from.id'=>'required|exists:flights,id',
            'flight_from.date'=>'required|date_format:Y-m-d',
            'flight_back.id'=>'required_with:flight_back|exists:flights,id',
            'flight_back.date'=>'required_with:flight_back|date_format:Y-m-d',
            'passengers'=>'required|array|between:1,8',
            'passengers.*.first_name' => 'required',
            'passengers.*.last_name' => 'required',
            'passengers.*.birth_date' => 'required|date_format:Y-m-d',
            'passengers.*.document_number' => 'required|numeric|digits:10'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
        {
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
        if ($request->flight_from) {
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
        }

        $passengers = $request->passengers;
        foreach ($passengers as &$el) {
            $el['booking_id'] = $idBooking;
        }
        Passenger::insert($passengers);

        return response()->json([
            'data'=>[
                'code'=>$code
            ]
        ] , 201);
    }

    public function search($code) {
        $getBookingId = Booking::where('code', '=', $code)->first();

        if (!$getBookingId) {
            return response()->json([
                'data'=>[]
            ]);
        }

        $getFlights = DB::select("SELECT f.id as 'flight_id', f.flight_code, f.cost,
                                    fromA.city as 'fromCity', fromA.name as 'fromAirport', fromA.iata as 'fromIata', f.time_from as 'fromTime',
                                    toA.city as 'toCity', toA.name as 'toAirport', toA.iata as 'toIata', f.time_to as 'toTime',
                                    b.date_from as 'fromDate', b.date_back as 'backDate', b.id as 'booking_id'
                                    FROM airports as fromA
                                    JOIN (flights as f JOIN airports as toA ON toA.id = f.to_id)
                                    ON fromA.id = f.from_id
                                    JOIN bookings as b ON b.flight_from = f.id OR b.flight_back = f.id
                                    WHERE b.code = ? AND b.id = ?;", [$code, $getBookingId->id]);

        $flights = [];
        $sumCostFlights = 0;
        $bookingId = NULL;
        if (count($getFlights) > 0) {
            $forDate = false;
            foreach ($getFlights as &$el) {
                $sumCostFlights = $sumCostFlights + $el->cost;

                array_push($flights , [
                    'flight_id'=>$el->flight_id,
                    'flight_code'=>$el->flight_code,
                    'from'=>[
                        'city'=>$el->fromCity,
                        'airport'=>$el->fromAirport,
                        'iata'=>$el->fromIata,
                        'date'=>$forDate ? $el->backDate : $el->fromDate,
                        'time'=>date('H:i' ,strtotime($el->fromTime))
                    ],
                    'to'=>[
                        'city'=>$el->toCity,
                        'airport'=>$el->toAirport,
                        'iata'=>$el->toIata,
                        'date'=>$forDate ? $el->backDate : $el->fromDate,
                        'time'=>date('H:i' ,strtotime($el->toTime))
                    ],
                    'cost'=>$el->cost,
                    'availability'=>156
                ]);
                $forDate= true;
            }

            $bookingId = $getFlights[0]->booking_id;
        }

        $passengers = Passenger::where('booking_id', '=', $bookingId)->get();
        $countPassengers = count($passengers);
        $sumCostFlights = $countPassengers > 0 ? $sumCostFlights * $countPassengers : $sumCostFlights;

        return response()->json([
            'data'=>[
                'code'=>$code,
                'cost'=>$sumCostFlights,
                'flights'=>$flights,
                'passengers'=>PassengerResource::collection($passengers)
            ]
        ]);
    }

    public function findByTokenUser(Request $request) {
        $token = $request->bearerToken();

        $getDN = User::where('api_token', $token)->first();

        if (!$getDN) {
            return response()->json([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorized'
                ]
            ], 401);
        }
        $getDN = $getDN->document_number;

        $arryBookingsId = DB::select("SELECT b.id FROM bookings as b
                            JOIN passengers as p ON p.booking_id = b.id
                            WHERE p.document_number = ?;", [$getDN]);

        $items = [];
        foreach ($arryBookingsId as $el) {
            $getFlights = DB::select("SELECT f.id as 'flight_id', f.flight_code, f.cost,
                                    fromA.city as 'fromCity', fromA.name as 'fromAirport', fromA.iata as 'fromIata', f.time_from as 'fromTime',
                                    toA.city as 'toCity', toA.name as 'toAirport', toA.iata as 'toIata', f.time_to as 'toTime',
                                    b.date_from as 'fromDate', b.date_back as 'backDate', b.id as 'booking_id', b.code
                                    FROM airports as fromA
                                    JOIN (flights as f JOIN airports as toA ON toA.id = f.to_id)
                                    ON fromA.id = f.from_id
                                    JOIN bookings as b ON b.flight_from = f.id OR b.flight_back = f.id
                                    WHERE b.id = ?;", [$el->id]);

            if (count($getFlights) > 0) {
                $sumCost = 0;
                $flights = [];

                foreach ($getFlights as $item) {
                    $sumCost = $sumCost + $item->cost;
                }

                $passengers = Passenger::where('booking_id', '=', $getFlights[0]->booking_id)->get();
                $countPassengers = count($passengers);
                $sumCost = $countPassengers > 0 ? $sumCost * $countPassengers : $sumCost;

                $forDate = false;
                foreach ($getFlights as $item) {
                    $flight = [
                        'flight_id'=>$item->flight_id,
                        'flight_code'=>$item->flight_code,
                        'from'=>[
                            'city'=>$item->fromCity,
                            'airport'=>$item->fromAirport,
                            'iata'=>$item->fromIata,
                            'date'=>$forDate ? $item->backDate : $item->fromDate,
                            'time'=>date('H:i' ,strtotime($item->fromTime))
                        ],
                            'to'=>[
                            'city'=>$item->toCity,
                            'airport'=>$item->toAirport,
                            'iata'=>$item->toIata,
                            'date'=>$forDate ? $item->backDate : $item->fromDate,
                            'time'=>date('H:i' ,strtotime($item->toTime))
                        ],
                        'cost'=>$item->cost,
                        'availability'=>156
                    ];
                    array_push($flights, $flight);
                    $forDate = true;
                }
                array_push($items, [
                    'code'=>$getFlights[0]->code,
                    'cost'=>$sumCost,
                    'flights'=>$flights,
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
