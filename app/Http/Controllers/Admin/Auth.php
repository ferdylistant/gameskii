<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Password as Pwd;

class Auth extends Controller
{
    public function __construct()
    {
        $this->admin = new User();
    }
    public function register(Request $request)
    {
        $name = $request->name;
        $email    = $request->email;
        $phone   = $request->phone;
        $password = $request->password;
        //validasi role
        if (auth('user')->user()->roles_id != "1") {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        //validasi form register
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:30',
            'email'    => 'required|string|max:30|email:rfc,dns,strict,spoof,filter|unique:users,email',
            'phone'   => 'required|min:11|string',
            'password' => ['required',Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()],
            'confirm_password' => 'required|same:password'
        ]);
        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }
        try {
            $created                = Carbon::now('Asia/Jakarta');
            $this->admin->name      = $name;
            $this->admin->email     = $email;
            $this->admin->phone     = $phone;
            $this->admin->password  = app('hash')->make($password);
            $this->admin->roles_id  = '2';
            $this->admin->ip_address= $request->getClientIp();

            if ($this->admin->save()) {
                return response()->json(['status' => 'success', 'message' => 'Registration successfully!'], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function login(Request $request)
    {
        //validasi login
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255|email:rfc,dns,strict,spoof,filter',
            'password' => 'required'
        ]);

        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }

        //cek ke database
        $login = $this->admin->where('email', $request->email)->first();
        if ($login->roles_id != '1' && $login->roles_id != '2')  {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        if (!$login || !app('hash')->check($request->password, $login->password)) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Your email or password incorrect!',
            ], 404);
        }
        $admin = new Client();
        try {
            $response =  $admin->post(config('service.passport.user.login_endpoint'), [
                'form_params' => [
                    'client_secret' => config('service.passport.user.client_secret'),
                    'grant_type'    => "password",
                    'client_id'     => config('service.passport.user.client_id'),
                    'username'      => $login->email,
                    'password'      => $request->password,
                ]
                ]);
            $current = Carbon::now('Asia/Jakarta');
            if ($response) {
                $login->forceFill([
                    'last_login' => $current->toDateTimeString(),
                    'ip_address' => $request->getClientIp()])->save();
            }
            return $response->getBody();
        } catch (BadResponseException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function logout(Request $request)
    {
        try {
            auth('user')->user()->tokens()->each(function ($token) {
                $token->delete();
            });
            return response()->json([
                'status'  => 'success',
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function changePassword(Request $request)
    {
        $id = auth('user')->user()->id;

        //validasi form ganti password
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => ['required',Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()],
            'confirm_password' => 'required|same:new_password'
        ]);
        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 400);
        }
        $sessAdmin = $this->admin->where('id', $id)->first();
        // return $sessAdmin;
        try {
            if (!$sessAdmin || !app('hash')->check($request->old_password, $sessAdmin->password)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Check your old password.",
                ], 400);
            } elseif (app('hash')->check($request->new_password, $sessAdmin->password)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Please enter a password which is not similar then current password.",
                ], 400);
            } else {
                $sessAdmin->password = app('hash')->make($request->new_password);
                $sessAdmin->ip_address = $request->getClientIp();
                $sessAdmin->save();
                return response()->json([
                    "status" => "success",
                    "message" => "Password updated successfully."
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
