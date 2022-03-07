<?php

namespace App\Http\Controllers\EndUser;

use Ramsey\Uuid\Uuid;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                "status" => "error",
                "message" => "You already have a '".$sessGame['game']['name']."' account",
            ], 409);
        }
        try {
            $this->gameAccount->id = Uuid::uuid4()->toString();
            $this->gameAccount->id_game_account = $request->id_game_account;
            $this->gameAccount->nickname = $request->nickname;
            $this->gameAccount->users_id = auth('user')->user()->id;
            $this->gameAccount->games_id = $sessGame['game']['id'];
            $this->gameAccount->is_online = '1';
            if ($this->gameAccount->save()) {
                $data = [
                    'game-account-data' => $this->gameAccount,
                    'game-data' => $sessGame
                ];
                $request->session()->put('game_account', $this->gameAccount);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Game account sign up successfully!',
                    'data' => $data
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
               'status' => 'error',
               'message' => $e->getMessage()
           ]);
        }
    }
    public function searchAccount(Request $request){
        $role = auth('user')->user()->roles_id;
        // return response()->json($role);
        if ($role != '3') {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        // return response()->json($sessGame);
        try{
            $key = explode(" ", request()->get('key'));
            $gameAccount = DB::table('game_accounts')->where('games_id', '=', $sessGame['game']['id'])
            ->where(function ($query) use ($key, $sessGame) {
                foreach ($key as $k) {
                    $query->where('id_game_account', 'like', '%' . $k . '%')->where('games_id', '=', $sessGame['game']['id']);
                }
            })
            ->orWhere(function ($query) use ($key, $sessGame) {
                foreach ($key as $k) {
                    $query->where('nickname', 'like', '%' . $k . '%')->where('games_id', '=', $sessGame['game']['id']);
                }
            })->get();
            if ($gameAccount->count() <= '0') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Game account not found!',
                    'data' => null
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Game account found!',
                'data' => $gameAccount
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
               'status' => 'error',
               'message' => $e->getMessage()
           ]);
        }


    }
}
