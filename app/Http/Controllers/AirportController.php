<?php

namespace App\Http\Controllers;

use App\Http\Resources\AirportResource;
use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function search (Request $request) {
        $q = $request->get('query');

        return response()->json([
            'data'=>[
                'items'=>AirportResource::collection(
                    Airport::where('city', 'like', '%'.$q.'%')
                        ->orWhere('name', 'like', '%'.$q.'%')
                        ->orWhere('iata', 'like', '%'.$q.'%')
                        ->get())
            ]
        ]);
    }
}
