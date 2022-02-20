<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Notifications\MailVerifyNotification;

class Auth extends Controller
{
    public function __construct()
    {
        $this->endUser = new User();
    }
    public function register(Request $request)
    {
        $name = $request->name;
        $email    = $request->email;
        $phone   = $request->phone;
        $password = $request->password;
        $fb = $request->fb;
        $ig = $request->ig;
        $provinsi = $request->provinsi;
        $kabupaten = $request->kabupaten;
        $kecamatan = $request->kecamatan;
        $tgl_lahir = $request->tgl_lahir;
        $avatar = $request->avatar;
        //validasi form register
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:30',
            'email'    => 'required|string|max:30|email:rfc,dns,strict,spoof,filter|unique:users,email',
            'phone'   => 'required|min:11|string',
            'fb'   => 'required|min:3|string',
            'ig'   => 'required|min:3|string',
            'provinsi'   => 'required|min:4|string',
            'kabupaten'   => 'required|min:4|string',
            'kecamatan'   => 'required|min:4|string',
            'tgl_lahir'   => 'required',
            'avatar' => 'file|max:2048|image',
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
            if ($request->hasFile('avatar')) {
                $dataFile = $request->file('avatar');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $imageUrl = URL::to('/api/avatar/'.$imageName);
                $dataFile->move(storage_path('uploads/avatar'), $imageName);
                $this->endUser->avatar         = $imageUrl;
            }

            $this->endUser->name         = $name;
            $this->endUser->email        = $email;
            $this->endUser->phone        = $phone;
            $this->endUser->password     = app('hash')->make($password);
            $this->endUser->fb           = $fb;
            $this->endUser->ig           = $ig;
            $this->endUser->provinsi     = $provinsi;
            $this->endUser->kabupaten    = $kabupaten;
            $this->endUser->kecamatan    = $kecamatan;
            $this->endUser->tgl_lahir    = $tgl_lahir;
            $this->endUser->roles_id     = '3';
            $this->endUser->is_verified  = '0';
            $this->endUser->ip_address   = $request->getClientIp();
            if ($this->endUser->save()) {
                $emailVerify = $this->endUser->email;
                // return var_dump($emailVerify);
                $e_v_a = Carbon::now('Asia/Jakarta');
                $this->endUser->forceFill([
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $e_v_a->toDateTimeString()
                ])->save();
                $token = $this->endUser->remember_token;
                $details = [
                    'url' => URL::to('/api/email-verification?email='. $this->endUser->email .'&token=' . $token),
                    'name' => $this->endUser->name,
                    'email' => $this->endUser->email
                ];
                $this->endUser->notify(new MailVerifyNotification($details));
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Registration successfully! A verification link has been sent to your email account!'
                ], 201);
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
            'email' => 'required|string|max:255|email',
            'password' => 'required'
        ]);

        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }

        //cek ke database
        $data = $this->endUser->where('email', $request->email)->first();
        if (!$data) {
            return response()->json([
                'code' => 404,
                'status'  => 'error',
                'message' => 'Email not terdaftar!'
            ],404);
        }
        if ($data->roles_id != '3')  {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        if ($data->is_verified == '0') {
            return $this->sendEmailVerify($data);
        }
        if (!$data || !app('hash')->check($request->password, $data->password)) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Your email or password incorrect!',
            ], 404);
        }
        $endUser = new Client();
        try {
            $response =  $endUser->post(config('service.passport.user.login_endpoint'), [
                'form_params' => [
                    'client_secret' => config('service.passport.user.client_secret'),
                    'grant_type'    => "password",
                    'client_id'     => config('service.passport.user.client_id'),
                    'username'      => $data->email,
                    'password'      => $request->password,
                ]
                ]);
            $current = Carbon::now('Asia/Jakarta');
            if ($response) {
                $data->forceFill([
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
    public function sendEmailVerify($data)
    {
        $e_v_a = Carbon::now('Asia/Jakarta');
        $data->forceFill([
            'remember_token' => Str::random(60),
            'email_verified_at' => $e_v_a->toDateTimeString()
            ])->save();
        $token = $data->remember_token;
        // $url = 'http://api.gameski.com/api/email-verification?token=' . $token;
        $details = [
            'url' => URL::to('/api/email-verification?email='. $data->email .'&token=' . $token),
            'name' => $data->name,
            'email' => $data->email
        ];
        try {
            $data->notify(new MailVerifyNotification($details));

            return response()->json([
                'code' => 403,
                'status' => 'forbidden',
                'message' => 'Your account is not active, we have sent you a link to verify your email'
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function logout(Request $request)
    {
        try {
            $request->session()->remove('game_account');
            $request->session()->remove('gamedata');
            auth('user')->user()->tokens()->each(function ($token) {
                $token->delete();
            });
            return response()->json([
                'code' => 200,
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
}
