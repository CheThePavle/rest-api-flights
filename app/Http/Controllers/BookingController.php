<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use Illuminate\Http\Request;
use DB;
use Validator;

class BookingController extends Controller
{
    public function findSeatByCode($code) {
        $result = DB::select('SELECT p.id, p.place_from, p.place_back FROM bookings as b JOIN passengers as p ON p.booking_id = b.id WHERE b.code = ?', [$code]);

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
            -> update([$place=>$request->seat]);

        return $checkBooking;
    }
}
