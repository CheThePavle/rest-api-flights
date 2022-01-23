<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $rules = [
            'first_name'=>'required',
            'last_name'=>'required',
            'phone'=>'required|unique:users',
            'document_number'=>'required|numeric|digits:10',
            'password'=>'required'
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
            ])->setStatusCode(422);
        }

        User::create($data);

        return response()->json()->setStatusCode(204);
    }

    public function login(Request $request)
    {
        $data = $request->all();

        $rules = [
            'phone'=>'required',
            'password'=>'required'
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
            ])->setStatusCode(422);
        }

        $user = User::where('phone', $request->phone)->first();
        if($user) {
            if($request->password == $user->password) {
                return response()->json([
                    'data' => [
                        'token' => $user->generateToken(),
                    ]
                ]);
            }
        }

        return response()->json([
            'error' => [
                'code' => 401,
                'message' => 'Unauthorized',
                'errors' => [
                    'phone' => [
                        'phone or password incorrect',
                    ]
                ]
            ]
        ], 401);
    }
}
