<?php

namespace App\Http\Controllers\Api;

use App\Traits\ReturnResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ReturnResponse;
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password');

        if(!$token = auth()->guard('api')->attempt($credentials)) {

            return $this->notfound(null, 'Email or Password is incorrect');
        }

        $data = [
            'user'  => auth()->guard('api')->user(),  
            'token' => $token
        ];

        return $this->success($data, 'Login berhasil');
    }
}
