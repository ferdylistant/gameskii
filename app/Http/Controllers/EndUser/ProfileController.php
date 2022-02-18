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
        $userGame = $request->session()->get('game_account');
        // return response()->json($userGame);
        if ($userGame == null) {
            return response()->json([
                'code' => 408,
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $user = $this->user->where('id', $userGame[0]->users_id)->first();
            $gameAccount = $this->gameAccount->where('users_id', $userGame[0]->users_id)->first();
            $socialFollow = $this->socialFollow->where('game_accounts_id', $userGame[0]->id_game_account)->get();
            foreach ($socialFollow as $item) {
                $userFollow[] = $this->gameAccount->where('id', $item->acc_following_id)->get();
            }
            $dataUser = [
                'name' => $user->name,
                'email' => $user->email
            ];
            $dataGame = [
                'id' => $userGame[0]->id_game_account,
                'nickname' => $userGame[0]->nickname,
            ];
            $dataSocial = [
                'count' => count($socialFollow),
                'data' => $userFollow
            ];

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data-user' => $dataUser,
                'data-game-account' => $dataGame,
                'data-following' => $dataSocial
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
