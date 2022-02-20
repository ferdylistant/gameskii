<?php

namespace App\Http\Controllers\EndUser;

use Ramsey\Uuid\Uuid;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GameAccountController extends Controller
{
    public function __construct()
    {
        $this->gameAccount = new GameAccount();
    }
    public function create(Request $request)
    {
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        // return response()->json($role);
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        //validasi form register
        $validator = Validator::make($request->all(), [
            'id_game_account' => 'required|max:30|unique:game_accounts,id_game_account',
            'nickname' => 'required|string|min:3|max:20',
        ]);
        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }
        if ($this->gameAccount->where('users_id', '=', auth('user')->user()->id)->where('games_id', '=',$sessGame['game']['id'])->exists()) {
            return response()->json([
                "code" => 409,
                "status" => "error",
                "message" => "You already have a '".$sessGame['game']['name']."' account"
            ], 409);
        }
        try {
            $this->gameAccount->id = Uuid::uuid4()->toString();
            $this->gameAccount->id_game_account = $request->id_game_account;
            $this->gameAccount->nickname = $request->nickname;
            $this->gameAccount->users_id = auth('user')->user()->id;
            $this->gameAccount->games_id = $sessGame['game']['id'];
            $this->gameAccount->is_online = '0';
            if ($this->gameAccount->save()) {
                $request->session()->put('game_account', $this->gameAccount);
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Game account sign up successfully!',
                    'data' => $this->gameAccount
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
               'status' => 'error',
               'message' => $e->getMessage()
           ]);
        }
    }
    public function login(Request $request)
    {
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        // return response()->json();
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        //validasi form register
        $validator = Validator::make($request->all(), [
            'id_game_account' => 'required|max:30',
            'nickname' => 'required|string|min:3|max:20',
        ]);
        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }
        try {
            $login = $this->gameAccount->where('id_game_account', '=', $request->id_game_account)
                ->where(
                    'games_id',
                    '=',
                    $sessGame['game']['id']
                )->where('nickname', '=', $request->nickname)->where('users_id', '=', auth('user')->user()->id)->first();
            // return response()->json($login);
            if (!$login) {
                return response()->json([
                'code' => 410,
                'status' => 'error',
                'message' => 'Something went wrong'
            ], 410);
            }
            $request->session()->put('game_account', $login);
            return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Game account sign in successfully!'
                ], 200);
        } catch (\Exception $e) {
            return response()->json([
               'status' => 'error',
               'message' => $e->getMessage()
           ]);
        }
    }
}
