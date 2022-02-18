<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Game;
use App\Models\User;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use App\Models\TopBannerGame;
use App\Models\BottomBannerGame;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class DashboardGame extends Controller
{
    public function __construct()
    {
        $this->game = new Game();
        $this->topBanner = new TopBannerGame();
        $this->bottomBanner = new BottomBannerGame();
        $this->user = new User();
        $this->gameAccount = new GameAccount();
        $this->socialFollow = new SocialFollow();
    }
    public function getDashboard(Request $request)
    {
        $sessGame = $request->session()->get('gamedata');
        $userGame = $request->session()->get('game_account');
        return response()->json($sessGame);
        if ($userGame == null) {
            auth('user')->user()->tokens()->each(function ($token) {
                $token->delete();
            });
            return response()->json([
                'code' => 408,
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $game = $this->game->where('id',$sessGame['game']['id'])->first();
            $topBanner = $this->topBanner->where('games_id',$game->id)->get();
            $bottomBanner = $this->bottomBanner->where('games_id',$game->id)->get();
            foreach ($topBanner as $result) {
                $top[] = URL::to('/api/banner-game/top/'.$result->path);
            }
            foreach ($bottomBanner as $value) {
                $title[] = $value->title;
                $bottom[] = URL::to('/api/banner-game/bottom/'.$value->path);
            }
            $data = [
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                    'picture' => URL::to('/api/picture-game/'.$game->picture),
                    'created_at' => $game->created_at,
                    'updated_at' => $game->updated_at
                ],
                'top-banner' => [
                    'url' => $top,
                ],
                'bottom-banner' => [
                    'title' => $title,
                    'url' => $bottom
                ]

            ];
            $user = $this->user->where('id', $userGame[0]->users_id)->first();
            if (!$user) {
                return response()->json([
                    'code' => 410,
                    'status' => 'error',
                    'message' => 'Something went wrong'
                ], 410);
            }
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
