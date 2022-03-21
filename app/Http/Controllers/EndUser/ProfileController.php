<?php

namespace App\Http\Controllers\EndUser;

use App\Models\User;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->gameAccount = new GameAccount();
        $this->socialFollow = new SocialFollow();
    }
    public function getProfile(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
        $sessGameAccount = $request->session()->get('game_account');
        $sessGame = $request->session()->get('gamedata');
        if (($sessGameAccount == null) || ($sessGame == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $user = $this->user->join('game_accounts', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'game_accounts.games_id', '=', 'games.id')
                ->where('users.id', auth('user')->user()->id)
                ->where('game_accounts.id_game_account', $sessGameAccount->id_game_account)
                ->where('games.id', $sessGame['game']['id'])
                ->select('users.*',
                'game_accounts.id_game_account as game_account_id',
                'game_accounts.nickname',
                'game_accounts.games_id',
                'games.name as game_name')
                ->first();
            $dataUser = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'fb' => $user->fb,
                    'ig' => $user->ig,
                    'provinsi' => $user->provinsi,
                    'kabupaten' => $user->kabupaten,
                    'kecamatan' => $user->kecamatan,
                    'tgl_lahir' => $user->tgl_lahir,
                    'avatar' => $user->avatar,
                    'roles_id' => $user->roles_id,
                    'is_verified' => $user->is_verified,
                    'ip_address' => $user->ip_address,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login' => $user->last_login,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'game_account' => [
                    'id_game_account' => $user->game_account_id,
                    'nickname' => $user->nickname,
                    'ranks_id' => $user->ranks_id,
                    'rank_point' => $user->rank_point,
                    'games_id' => $user->games_id,
                    'game_name' => $user->game_name,
                ],
            ];

            return response()->json([
                'status' => 'success',
                'data' => $dataUser,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function updateProfile(Request $request)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            // return auth('user')->user();
            $sessGameAccount = $request->session()->get('game_account');
            $sessGame = $request->session()->get('gamedata');
            if (($sessGameAccount == null) || ($sessGame == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:3|max:30',
                'phone'   => 'required|min:11|string',
                'fb'   => 'required|min:3|string',
                'ig'   => 'required|min:3|string',
                'provinsi'   => 'required|min:4|string',
                'kabupaten'   => 'required|min:4|string',
                'kecamatan'   => 'required|min:4|string',
                'tgl_lahir'   => 'required',
                'avatar' => 'image|mimes:jpeg,png,jpg,svg|max:2048',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $user = $this->user->join('game_accounts', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'game_accounts.games_id', '=', 'games.id')
                ->where('users.id', auth('user')->user()->id)
                ->where('game_accounts.id_game_account', $sessGameAccount->id_game_account)
                ->where('games.id', $sessGame['game']['id'])
                ->select('users.*')
                ->first();
            if ($user == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            if ($request->hasFile('avatar')) {
                $dataFile = $request->file('avatar');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $imageUrl = URL::to('/api/avatar/'.$imageName);
                $current_image_path = storage_path('uploads/avatar/'.$user->avatar);
                if (file_exists($current_image_path)) {
                    File::delete($current_image_path);
                }
                $dataFile->move(storage_path('uploads/avatar'), $imageName);
                $user->avatar         = $imageUrl;
            }
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->fb = $request->fb;
            $user->ig = $request->ig;
            $user->provinsi = $request->provinsi;
            $user->kabupaten = $request->kabupaten;
            $user->kecamatan = $request->kecamatan;
            $user->tgl_lahir = $request->tgl_lahir;
            $user->ip_address = $request->getClientIp();
            if ($user->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Profile updated successfully'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function changePassword(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
        $sessGameAccount = $request->session()->get('game_account');
        $sessGame = $request->session()->get('gamedata');
        if (($sessGameAccount == null) || ($sessGame == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => ['required',Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()],
            'confirm_password' => 'required|same:new_password',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }
        $dataUser = $this->user->join('game_accounts', 'users.id', '=', 'game_accounts.users_id')
            ->join('games', 'game_accounts.games_id', '=', 'games.id')
            ->where('users.id', auth('user')->user()->id)
            ->where('game_accounts.id_game_account', $sessGameAccount->id_game_account)
            ->where('games.id', $sessGame['game']['id'])
            ->select('users.*')
            ->first();
        try {
            if (!$dataUser || !Hash::check($request->old_password, $dataUser->password)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Check your old password.",
                ],400);
            } else if (Hash::check($request->new_password, $dataUser->password)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Please enter a password which is not similar then current password.",
                ],400);
            } else {
                $dataUser->password = app('hash')->make($request->new_password);
                $dataUser->ip_address = $request->getClientIp();
                if ($dataUser->save())
                {
                    return response()->json([
                        "status" => "success",
                        "message" => "Password updated successfully."
                    ],201);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
