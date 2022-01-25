<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;

class FlightController extends Controller
{
    public function getFlights($from, $to, $date)
    {
        $result = DB::select("SELECT f.id as 'flight_id', f.flight_code, f.cost,
                            fromA.city as 'fromCity', fromA.name as 'fromAirport', fromA.iata as 'fromIata', f.time_from as 'fromTime',
                            toA.city as 'toCity', toA.name as 'toAirport', toA.iata as 'toIata', f.time_to as 'toTime'
                            FROM airports as fromA
                            JOIN (flights as f JOIN airports as toA ON toA.id = f.from_id)
                            ON fromA.id = f.to_id
                            WHERE fromA.iata = ? AND toA.iata = ?;", [$from, $to]);

        $flights = [];
        if (count($result) > 0) {
            foreach ($result as &$el) {
                array_push($flights , [
                    'flight_id'=>$el->flight_id,
                    'flight_code'=>$el->flight_code,
                    'from'=>[
                        'city'=>$el->fromCity,
                        'airport'=>$el->fromAirport,
                        'iata'=>$el->fromIata,
                        'date'=>$date,
                        'time'=>date('H:i' ,strtotime($el->fromTime))
                    ],
                    'to'=>[
                        'city'=>$el->toCity,
                        'airport'=>$el->toAirport,
                        'iata'=>$el->toIata,
                        'date'=>$date,
                        'time'=>date('H:i' ,strtotime($el->toTime))
                    ],
                    'cost'=>$el->cost,
                    'availability'=>156
                ]);
            }
        }

        return $flights;
    }

    public function search(Request $request)
    {
        $rules = [
            'from' => 'required|exists:airports,iata',
            'to' => 'required|exists:airports,iata',
            'date1' => 'required|date_format:Y-m-d',
            'date2' => 'date_format:Y-m-d',
            'passengers' => 'required|numeric|between:1,8',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json([
                'error' => [
                    "code" => 422,
                    "message" => "Validation error",
                    "errors" => $validator->errors(),
                ]
            ], 422);
        }

        $flightsFrom = $this->getFlights($request->from, $request->to, $request->date1);

        $flightsBack = [];
        if($request->get('date2')) {
            $flightsBack = $this->getFlights($request->to, $request->from, $request->date2);
        }

        return response()->json([
            'data' => [
                'flights_to' => $flightsFrom,
                'flights_back' => $flightsBack
            ]
        ]);
    }
}
