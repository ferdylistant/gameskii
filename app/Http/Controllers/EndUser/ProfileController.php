<?php

namespace App\Http\Controllers\EndUser;

use App\Models\User;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
            ], 401);
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
}
