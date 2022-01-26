<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Validator;

class AuthController extends Controller
{
    public function register(Request $request) {
        $rules = [
            'first_name'=>'required',
            'last_name'=>'required',
            'phone'=>'required|unique:users,phone',
            'document_number'=>'required|numeric|digits:10',
            'password'=>'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Validation error',
                    'errors'=>$validator->errors()
                ]
            ], 422);
        }

        User::create($request->all());

        return response()->json(NULL, 204);
    }

    public function login(Request $request) {
        $rules = [
            'phone'=>'required',
            'password'=>'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error'=>[
                    'code'=>422,
                    'message'=>'Validation error',
                    'errors'=>$validator->errors()
                ]
            ], 422);
        }

        $check = User::where('phone', '=', $request->phone)
                        ->where('password', '=', $request->password)->first();

        if ($check) {
            return response()->json([
                'data'=>[
                    'token'=>$check->generateToken()
                ]
            ]);
        }

        return response()->json([
            'error'=>[
                'code'=>401,
                'message'=>'Unauthorized',
                'errors'=>[
                    'phone'=>['phone or password incorrect']
                ]
            ]
        ], 401);
    }
}
