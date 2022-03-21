<?php

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use App\Models\User;
use App\Models\Scrim;
use App\Models\ScrimMatch;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Http\Controllers\Controller;

class ScrimProgressController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->scrim = new Scrim();
        $this->scrimMatch = new ScrimMatch();
        $this->gameAccount = new GameAccount();
        $this->scrimProg = new ScrimProgress();

    }
    public function getScrimProgress(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id == '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource.'
            ], 403);
        }
        $dataScrim = $this->scrim->where('status', '=', 'On')->get();
        if ($dataScrim->count() < 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'No scrim is currently active.'
            ], 404);
        }
        $dataScrimProgress = $this->scrimProg->join('scrims', 'scrims.id', '=', 'scrim_progress.scrim_id')
            ->join('scrim_matches', 'scrim_matches.id', '=', 'scrim_progress.scrim_match_id')
            ->join('games', 'games.id', '=', 'scrims.games_id')
            ->join('users', 'users.id', '=', 'scrim_progress.user_id')
            ->select('scrim_progress.*', 'scrims.name as scrim_name', 'games.name as game_name', 'users.name as user_name')
            ->where('scrim_progress.scrim_id', '=', $dataScrim[0]->id)
            ->get();
    }
}
