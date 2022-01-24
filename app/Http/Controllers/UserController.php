<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function findByToken(Request $request)
    {
        $token = $request->bearerToken();

        $check = User::where('api_token', $token)->first();

        if ($check) {
            return [
                'first_name'=>$check->first_name,
                'last_name'=>$check->last_name,
                'phone'=>$check->phone,
                'document_number'=>$check->document_number
            ];
        }

        return response()->json([
            'error' => [
                'code' => 401,
                'message' => 'Unauthorized'
            ]
        ], 401);
    }
}
