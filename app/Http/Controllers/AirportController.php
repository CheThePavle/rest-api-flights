<?php

namespace App\Http\Controllers;

use App\Http\Resources\AirportResource;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function search (Request $request) {
        $q = $request->get('query');

        $result = Airport::where('city', 'like', '%'.$q.'%')
            ->orWhere('name', 'like', '%'.$q.'%')
            ->orWhere('iata', 'like', '%'.$q.'%')
            ->get();

        return response()->json([
            'data'=>[
                'items'=>AirportResource::collection($result)
            ]
        ]);
    }
}
