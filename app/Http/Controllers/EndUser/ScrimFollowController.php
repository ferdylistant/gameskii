<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Scrim;
use App\Models\ScrimMatch;
use App\Models\GameAccount;
use App\Models\ScrimFollow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ScrimFollowController extends Controller
{
    public function __construct()
    {
        $this->scrimFollow = new ScrimFollow();
        $this->scrim = new Scrim();
        $this->gameAccount = new GameAccount();
        $this->scrimMatch = new ScrimMatch();
        $this->rank = new Rank();
    }
    public function getScrimFollowed(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 401);
            }
            $scrims = $this->scrimFollow->join('scrims', 'scrims.id', '=', 'scrim_follows.scrims_id')
                ->join('games', 'games.id', '=', 'scrims.games_id')
                ->join('game_accounts', 'game_accounts.id', '=', 'scrim_follows.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->select('scrims.*')
                ->where('scrim_follows.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->get();
            if ($scrims->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have no scrim followed.'
                ], 404);
            }
            foreach ($scrims as $scrim) {
                $data[] = [
                    'id' => $scrim->id,
                    'games_id' => $scrim->games_id,
                    'rank' => $this->rank->where('id',$scrim->ranks_id)
                    ->select('id','class')
                    ->first(),
                    'name_party' => $scrim->name_party,
                    'image' => URL::to('/api/picture-scrim/'.$scrim->image),
                    'team_play' => $this->scrimMatch->where('scrims_id','=', $scrim->id)->get()->count(),
                    'quota' => $scrim->quota,
                    'scrim_system' => $scrim->scrim_system,
                    'scrim_date' => $scrim->scrim_date,
                    'status' => $scrim->status,
                    'result' => $scrim->result,
                    'created_at' => $scrim->created_at,
                    'updated_at' => $scrim->updated_at,
                ];
            }
            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function followScrim(Request $request,$idScrim)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 401);
            }
            $scrim = $this->scrim->where('id',$idScrim)->first();
            if (!$scrim) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found.'
                ], 404);
            }
            $myScrim = $this->scrim->where('id','=',$idScrim)
                ->where('game_accounts_id','=',$sessGameAccount->id_game_account)
                ->first();
            if ($myScrim) {
                return response()->json([
                    'status' => 'error',
                    'message' => "This is your scrim. You can't follow it."
                ], 403);
            }
            $scrimFollow = $this->scrimFollow->where('scrims_id','=',$idScrim)
                ->where('game_accounts_id','=',$sessGameAccount->id_game_account)
                ->first();
            if ($scrimFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already follow this scrim.'
                ], 400);
            }
            $this->scrimFollow->scrims_id = $idScrim;
            $this->scrimFollow->game_accounts_id = $sessGameAccount->id_game_account;
            if ($this->scrimFollow->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You have successfully follow this scrim.'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function unfollowScrim(Request $request,$idScrim)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 401);
            }
            $scrimFollow = $this->scrimFollow->where('game_accounts_id','=',$sessGameAccount->id_game_account)
                ->where('scrims_id','=',$idScrim)
                ->first();
            if (!$scrimFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You don't follow this scrim."
                ], 400);
            }
            if ($scrimFollow->delete()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You have successfully unfollow this scrim.'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
