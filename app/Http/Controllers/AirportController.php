<?php

namespace App\Http\Controllers;

use App\Http\Resources\AirportResource;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function search(Request $request) {
        $q = $request->get('query');
        if (!$q) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'query параметр не передан'
                ]
            ], 422);
        }

        $airports = Airport::where('city', 'like', '%'.$q.'%')
            ->orWhere('name', 'like', '%'.$q.'%')
            ->orWhere('iata', 'like', '%'.$q.'%')->get();

        return response()->json([
            'data'=>[
                'items'=>AirportResource::collection($airports)
            ]
        ]);
    }
}
