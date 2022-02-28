<?php

namespace App\Http\Controllers\SocialAuth;

use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use GuzzleHttp\Exception\BadResponseException;

class SocialAccountController extends Controller
{
    public function __construct() 
    {
        $this->endUser = new User();
    }
    public function redirectToGoogle()
    {
        // return "redirectToGoogle";
        try {
            // Socialite::driver('google')->redirect();
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            // You should show something simple fail message
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function callbackFromGoogle(Request $request)
    {
        $userSocial = Socialite::driver('google')->user();
        $userdata = $this->endUser->where('google_id', $userSocial->id)->first();
        if ($userdata) {
            return $this->loginGuzzle($request, $userSocial, $userdata);
        }
        $userdata = $this->endUser->where('email', $userSocial->email)->first();
        // return response($userdata);
        if ($userdata) {
            return $this->loginGuzzle($request, $userSocial, $userdata);
        }
        // return $this->createUser($request, $userSocial);
        try {
            $this->endUser->name = $userSocial->name;
            $this->endUser->email = $userSocial->email;
            $this->endUser->avatar = $userSocial->avatar;
            $this->endUser->roles_id = '3';
            if ($this->endUser->save()) {
                $response = $this->endUser->createToken('googleAuth')->accessToken;
            }
            $current = Carbon::now('Asia/Jakarta');
            if ($response) {
                $this->endUser->forceFill([
                    'google_id' => $userSocial->id,
                    'last_login' => $current->toDateTimeString(),
                    'ip_address' => $request->getClientIp()])->save();
            }
            // return $response->getBody();
            return response()->json([
                'token_type' => "Bearer",
                'expires_in' => 31535999,
                'access_token' => $response,
                'data' => $this->endUser
            ]);
        } catch (\Exception $e) {
            // You should show something simple fail message
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function createUserFromGoogle($request, $userSocial)
    {
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
            $this->endUser->name = $userSocial->name;
            $this->endUser->email = $userSocial->email;
            $this->endUser->phone = $request->phone;
            $this->endUser->password = $request->password;
            $this->endUser->phone = $request->phone;
            $this->endUser->fb = $request->fb;
            $this->endUser->ig = $request->ig;
            $this->endUser->avatar = $userSocial->avatar;
            $this->endUser->provinsi = $request->provinsi;
            $this->endUser->kabupaten = $request->kabupaten;
            $this->endUser->kecamatan = $request->kecamatan;
            $this->endUser->tgl_lahir = $request->tgl_lahir;
            $this->endUser->ip_address = $request->getClientIp();
            $this->endUser->email_verified_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
            $this->endUser->is_verified = '1';
            $this->endUser->google_id = $userSocial->id;
            $this->endUser->roles_id = '3';
            if ($request->hasFile('avatar')) {
                $dataFile = $request->file('avatar');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $imageUrl = URL::to('/api/avatar/'.$imageName);
                $dataFile->move(storage_path('uploads/avatar'), $imageName);
                $this->endUser->avatar         = $imageUrl;
            }
            if ($this->endUser->save()) {
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Registration successfully!'
                ]);
            }
        } catch (\Exception $e) {
            // You should show something simple fail message
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function loginGuzzle($request, $userSocial, $userdata)
    {
        // $decrypt= Crypt::decrypt($userdata->password);
        // return response()->json($userSocial);
        // return "hehe";
        // $dd = Auth::guard('user')->login($userSocial);
        // return response()->json($dd);

        $userGuzzle = new Client();
        try {
            $response = $userdata->createToken('googleAuth')->accessToken;
            // $userGuzzle->get(config('service.passport.user.login_endpoint'), [
            //     'form_params' => [
            //         'client_secret' => config('service.passport.user.client_secret'),
            //         'grant_type'    => "client_credentials",
            //         'client_id'     => config('service.passport.user.client_id'),
            //         'scope'      => '*',
            //         // 'code' => $request->code,
            //     ]
            //     ]);
            // $response =  $userGuzzle->post(URL::to('/v1/oauth/token'), [
            //     'form_params' => [
            //         'client_secret' => '8Rh0tKFeRyxSpRPRtb9BpKSddabwGuY4g5n8lgeA',
            //         'grant_type'    => "refresh_token",
            //         'refresh_token' => $token,
            //         'client_id'     => '5',
            //         'scope'      => '*',
            //         // 'code' => $request->code,
            //     ]
            //     ]);
            // $response = $token->post(URL::to('/v1/oauth/token'), [
            //     'form_params' => [
            //         'grant_type' => 'client_credentials',
            //         'client_id' => '5',
            //         'client_secret' => '8Rh0tKFeRyxSpRPRtb9BpKSddabwGuY4g5n8lgeA',
            //         'scope' => '*'
            //         // 'redirect_uri' => 'http://api.gameski.com/callback',
            //         // 'code' => $token,
            //     ]
            // ]);
            $current = Carbon::now('Asia/Jakarta');
            if ($response) {
                $userdata->forceFill([
                    'google_id' => $userSocial->id,
                    'last_login' => $current->toDateTimeString(),
                    'ip_address' => $request->getClientIp()])->save();
            }
            // return $response->getBody();
            return response()->json([
                'token_type' => "Bearer",
                'expires_in' => 31535999,
                'access_token' => $response
            ]);
        } catch (BadResponseException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
