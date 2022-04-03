<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\Scrim;
use Ramsey\Uuid\Uuid;
use App\Models\ScrimMatch;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Events\ScrimProgress as ScrimProgressEvent;

class ScrimProgressController extends Controller
{
    public function __construct()
    {
        $this->scrim = new Scrim();
        $this->gameAccount = new GameAccount();
        $this->scrimProgress = new ScrimProgress();
        $this->scrimMatch = new ScrimMatch();
    }
    public function uploadResultMatch(Request $request, $idScrim)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session timeout"
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "Scrim not found"
                ], 404);
            }
            $scrimMatch = $this->scrimMatch->join('scrims','scrims.id','=','scrim_matches.scrims_id')
            ->join('teams','scrim_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->join('game_accounts','team_players.game_accounts_id','=','game_accounts.id_game_account')
            ->join('users','game_accounts.users_id','=','users.id')
            ->where('scrim_matches.scrims_id','=',$scrim->id)
            ->where('scrim_matches.status_match','=','1')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->select('scrim_matches.id','scrim_matches.scrims_id','scrim_matches.teams_id','scrims.name_party','teams.name as team_name','teams.ranks_id','users.phone','scrim_matches.status_match','scrim_matches.result')
            ->first();
            if ($scrimMatch == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "You are not a player team in this scrim match"
                ], 404);
            }
            if ($sessGame['game']['id'] == '3d9fe0a9-a052-48c9-95c6-1ca494fe93c3') { //Mobile Legend
                $validator = Validator::make($request->all(), [
                    'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
                    'round' => 'required',
                    'result' => 'required',
                    'total_kills' => 'required|numeric',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()
                    ], 400);
                }
                $dataFile = $request->file('screenshot');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $data = [
                    'id' => Uuid::uuid4()->toString(),
                    'scrims_id' => $scrim->id,
                    'scrim_matches_id' => $scrimMatch->id,
                    'screenshot' => $imageName,
                    'round' => $request->round,
                    'result' => $request->result,
                    'total_kills' => $request->total_kills,
                    'created_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                    'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                ];
                event(new ScrimProgressEvent($data));

                $dataFile->move(storage_path('uploads/scrim_progress'), $imageName);
                return response()->json([
                    "status" => "success",
                    "message" => "Successfully upload result match, waiting for admin approval"
                ], 200);
            }
            if ($sessGame['game']['id'] == '42e7db33-330b-4b4b-87c8-d255f7dca901') { //PUBG Mobile
                $validator = Validator::make($request->all(), [
                    'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
                    'round' => 'required',
                    'result' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()
                    ], 400);
                }
                $dataFile = $request->file('screenshot');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $data = [
                    'id' => Uuid::uuid4()->toString(),
                    'scrims_id' => $scrim->id,
                    'scrim_matches_id' => $scrimMatch->id,
                    'screenshot' => $imageName,
                    'round' => $request->round,
                    'result' => $request->result,
                    'created_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                    'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                ];
                event(new ScrimProgressEvent($data));

                $dataFile->move(storage_path('uploads/scrim_progress'), $imageName);
                return response()->json([
                    "status" => "success",
                    "message" => "Successfully upload result match, waiting for admin approval"
                ], 200);
            }
            if ($sessGame['game']['id'] == '7c26d6b9-72a7-4b56-90de-486e71923f48') { //Free Fire
                $validator = Validator::make($request->all(), [
                    'screenshot' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
                    'round' => 'required',
                    'result' => 'required',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()
                    ], 400);
                }
                $dataFile = $request->file('screenshot');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $data = [
                    'id' => Uuid::uuid4()->toString(),
                    'scrims_id' => $scrim->id,
                    'scrim_matches_id' => $scrimMatch->id,
                    'screenshot' => $imageName,
                    'round' => $request->round,
                    'result' => $request->result,
                    'created_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                    'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                ];
                event(new ScrimProgressEvent($data));

                $dataFile->move(storage_path('uploads/scrim_progress'), $imageName);
                return response()->json([
                    "status" => "success",
                    "message" => "Successfully upload result match, waiting for admin approval"
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
