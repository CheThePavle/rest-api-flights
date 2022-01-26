<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request) {
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

        return response()->json([
            'first_name'=>$user->first_name,
            'last_name'=>$user->last_name,
            'phone'=>$user->phone,
            'document_number'=>$user->document_number
        ]);
    }
}
