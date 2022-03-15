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
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
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
}
