<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Passenger;
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

    public function selectSeat($code ,Request $request) {
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
}
