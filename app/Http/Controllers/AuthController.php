<?php

namespace App\Http\Controllers;

use App\Models\AuthRegisterModel;
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

        AuthRegisterModel::create($data);

        return response()->json()->setStatusCode(204);
    }
}
