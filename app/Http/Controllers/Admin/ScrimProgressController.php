<?php

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use App\Models\Rank;
use App\Models\User;
use App\Models\Scrim;
use App\Models\ScrimMatch;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

class ScrimProgressController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->rank = new Rank();
        $this->scrim = new Scrim();
        $this->scrimMatch = new ScrimMatch();
        $this->gameAccount = new GameAccount();
        $this->scrimProg = new ScrimProgress();

    }
    public function getScrimProgress(Request $request, $idGame)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $dataScrimProgress = $this->scrimProg->join('scrims', 'scrims.id', '=', 'scrim_progress.scrim_id')
                ->join('ranks', 'ranks.id', '=', 'scrims.ranks_id')
                ->join('scrim_matches', 'scrim_matches.id', '=', 'scrim_progress.scrim_match_id')
                ->join('teams', 'teams.id', '=', 'scrim_matches.teams_id')
                ->join('games', 'games.id', '=', 'scrims.games_id')
                ->join('users', 'users.id', '=', 'scrim_progress.user_id')
                ->where('scrims.games_id', $idGame)
                ->where('scrims.status', '=', 'On')
                ->select('scrim_progress.*',
                'scrims.name_party',
                'ranks.class',
                'scrims.image',
                'scrims.quota',
                'scrims.scrim_system',
                'scrims.scrim_date',
                'scrims.status',
                'scrims.result as scrim_result',
                'teams.name as team_name',
                'games.name as game_name',
                'users.name as user_name')
                ->get();

            foreach ($dataScrimProgress as $value) {
                $result[] = [
                    'scrim' => [
                        'id' => $value->scrims_id,
                        'name' => $value->name_party,
                        'game' => $value->game_name,
                        'rank_requirement' => $value->class,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'scrim_result' => $value->scrim_result,
                    ],
                    'scrim_match' => [
                        'id_scrim_match' => $value->scrim_matches_id,
                        'team_name' => $value->team_name,

                    ],
                ];
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
