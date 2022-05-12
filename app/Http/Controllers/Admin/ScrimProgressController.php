<?php

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use App\Models\Rank;
use App\Models\User;
use App\Models\Scrim;
use App\Models\ScrimMatch;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Models\ScrimMatchDetail;
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
        $this->scrimMatchDetail = new ScrimMatchDetail();

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
            $dataScrimProgress = $this->scrimProg->join('scrims', 'scrims.id', '=', 'scrim_progress.scrims_id')
                ->join('ranks', 'ranks.id', '=', 'scrims.ranks_id')
                ->join('scrim_matches', 'scrim_matches.id', '=', 'scrim_progress.scrim_matches_id')
                ->join('teams', 'teams.id', '=', 'scrim_matches.teams_id')
                ->join('games', 'games.id', '=', 'scrims.games_id')
                ->where('scrims.games_id', $idGame)
                ->where('scrims.status', '=', 'On')
                ->where('scrim_progress.status_action', '=', 'pending')
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
                'games.name as game_name')
                ->get();
            if($dataScrimProgress->count() < 1){
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data found.',
                    'data' => $dataScrimProgress
                ], 404);
            }
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
                    'scrim_match_reporter' => [
                        'id_scrim_progress' => $value->id,
                        'id_scrim_match' => $value->scrim_matches_id,
                        'team_name' => $value->team_name,
                        'round' => $value->round,
                        'result_match' => $value->result,
                        'total_kills' => $value->total_kills,
                        'screenshot' => URL::to('/api/picture-scrim-progress/'.$value->screenshot),
                        'status_action' => $value->status_action,
                        'note_action' => $value->note_action,
                        'created_at' => $value->created_at,
                    ],
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully get data',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function showMatchBattle(Request $request, $idScrimProgress)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $dataScrimProgress = $this->scrimProg->join('scrims', 'scrims.id', '=', 'scrim_progress.scrims_id')
            ->join('scrim_matches', 'scrim_matches.id', '=', 'scrim_progress.scrim_matches_id')
            ->where('scrim_progress.id', $idScrimProgress)
            ->select('scrim_progress.*','scrims.name_party','scrim_matches.teams_id')
            ->first();
            if ($dataScrimProgress == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim progress not found'
                ], 404);
            }
            $teamMatch = $this->scrimMatchDetail->where('scrims_id', '=', $dataScrimProgress->scrims_id)
            ->where('teams_id', '=', $dataScrimProgress->teams_id)
            ->first();
            if ($teamMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $result = [
                'team1' => $this->scrimMatch->join('teams','scrim_matches.teams_id','=','teams.id')
                ->where('scrim_matches.teams_id','=',$teamMatch->teams1_id)
                ->where('scrim_matches.scrims_id','=',$dataScrimProgress->scrims_id)
                ->select(
                    'scrim_matches.id',
                    'scrim_matches.teams_id',
                    'teams.name as team_name',
                    'scrim_matches.round',
                    'scrim_matches.score',
                    'scrim_matches.result',
                    )->first(),
                'team2' => $this->scrimMatch->join('teams','scrim_matches.teams_id','=','teams.id')
                ->where('scrim_matches.teams_id','=',$teamMatch->teams2_id)
                ->where('scrim_matches.scrims_id','=',$dataScrimProgress->scrims_id)
                ->select(
                    'scrim_matches.id',
                    'scrim_matches.teams_id',
                    'teams.name as team_name',
                    'scrim_matches.round',
                    'scrim_matches.score',
                    'scrim_matches.result',
                    )->first(),
            ];
            return response()->json([
                'status' => 'success',
                'message' => 'Scheme bracket',
                'id_scrim' => $dataScrimProgress->scrims_id,
                'name_party' => $dataScrimProgress->name_party,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function confirmReport(Request $request,$idScrim,$idMatch)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $validator = Validator::make($request->all(), [
                'team_versus_name' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }
            $dataScrimMatchReporter = $this->scrimProg->join('scrims','scrims.id','=','scrim_progress.scrims_id')
                ->join('scrim_matches','scrim_matches.id','=','scrim_progress.scrim_matches_id')
                ->join('teams','teams.id','=','scrim_matches.teams_id')
                ->join('games','games.id','=','scrims.games_id')
                ->where('scrim_progress.scrims_id',$idScrim)
                ->where('scrim_progress.scrim_matches_id',$idMatch)
                ->select('scrim_progress.*','scrims.name_party','scrims.image','scrims.quota','scrims.scrim_system','scrims.scrim_date','scrims.status','scrims.result as scrim_result','teams.name as team_name','games.name as game_name')
                ->first();
            $dataScrimProgress = $this->scrimProg->where('scrims_id', $idScrim)
                ->where('scrim_matches_id', $idMatch)
                ->where('status_action', '=', 'pending')
                ->update([
                    'status_action' => 'confirmed',
                    'note_action' => $request->note_action,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
