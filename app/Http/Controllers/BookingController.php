<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use DB;

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
}
