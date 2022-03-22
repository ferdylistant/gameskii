<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Team;
use App\Models\Scrim;
use Ramsey\Uuid\Uuid;
use App\Models\ScrimMatch;
use App\Models\TeamPlayer;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Http\Controllers\Controller;

class ScrimMatchController extends Controller
{
    public function __construct()
    {
        $this->rank = new Rank();
        $this->team = new Team();
        $this->scrim = new Scrim();
        $this->teamPlayer = new TeamPlayer();
        $this->scrimMatch = new ScrimMatch();
        $this->gameAccount = new GameAccount();
        $this->scrimProgress = new ScrimProgress();
    }
    public function joinRoom(Request $request, $idScrim)
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
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])
            ->first();
            if ($scrim == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $alreadyJoin = $this->scrimMatch->join('teams','teams.id','=','scrim_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->where('scrim_matches.scrims_id','=',$idScrim)
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=',1)
            ->first();
            if ($alreadyJoin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already join this scrim'
                ], 409);
            }
            $scrimOn = $scrim->where('status','=','On')->first();
            if ($scrimOn == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim is not on'
                ], 404);
            }
            $minRank = $this->rank->min('id');
            $scrimMaster = $scrimOn->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->first();
            if ($scrimMaster){
                $teamJoin = $this->team->join('team_players', 'teams.id', '=', 'team_players.teams_id')
                ->where('teams.games_id','=',$scrimMaster->games_id)
                ->where('team_players.game_accounts_id','=',$scrimMaster->game_accounts_id)
                ->where('team_players.status','=','1')
                ->first();
                if ($teamJoin == null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Team not found'
                    ], 404);
                }
                $isRank = $this->rank->where('id','=',$scrimMaster->ranks_id)->first();
                $rankPre = $this->rank->where('id','<',$scrimMaster->ranks_id)->max('id');
                $rankNext = $this->rank->where('id','>',$scrimMaster->ranks_id)->min('id');
                if (($teamJoin->ranks_id == null) && ($scrimMaster->ranks_id == $minRank)) {
                    $this->scrimMatch->id = Uuid::uuid4()->toString();
                    $this->scrimMatch->scrims_id = $scrimMaster->id;
                    $this->scrimMatch->teams_id = $teamJoin->teams_id;
                    $this->scrimMatch->result = 'On Going';
                    $this->scrimMatch->round = 'Not yet';
                    $this->scrimMatch->status_match = '1';
                    if ($this->scrimMatch->save())
                    {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'You are in the scrim, please wait for the other team',
                        ], 200);
                    }
                }
                if (($teamJoin->ranks_id == $isRank) || ($teamJoin->ranks_id == $rankPre) || ($teamJoin->ranks_id == $rankNext))
                {
                    $this->scrimMatch->id = Uuid::uuid4()->toString();
                    $this->scrimMatch->scrims_id = $scrimMaster->id;
                    $this->scrimMatch->teams_id = $teamJoin->teams_id;
                    $this->scrimMatch->result = 'On Going';
                    $this->scrimMatch->round = 'Not yet';
                    $this->scrimMatch->status_match = '1';
                    if ($this->scrimMatch->save())
                    {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'You are in the scrim, please wait for the other team',
                        ], 200);
                    }
                }
                return response()->json([
                    'status' => 'error',
                    'message' => "Your team rank is not suitable for this scrim"
                ], 403);
            }
            $teamJoin = $this->team->join('team_players', 'teams.id', '=', 'team_players.teams_id')
                ->where('teams.games_id','=',$scrimOn->games_id)
                ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
                ->where('team_players.status','=','1')
                ->first();
            if ($teamJoin == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found'
                ], 404);
            }
            $isRank = $this->rank->where('id','=',$scrimOn->ranks_id)->first();
            $rankPre = $this->rank->where('id','<',$scrimOn->ranks_id)->max('id');
            $rankNext = $this->rank->where('id','>',$scrimOn->ranks_id)->min('id');
            if (($teamJoin->ranks_id == null) && ($scrimOn->ranks_id == $minRank)) {
                $this->scrimMatch->id = Uuid::uuid4()->toString();
                $this->scrimMatch->scrims_id = $scrimOn->id;
                $this->scrimMatch->teams_id = $teamJoin->teams_id;
                $this->scrimMatch->result = 'On Going';
                $this->scrimMatch->round = 'Not yet';
                $this->scrimMatch->status_match = '0';
                if ($this->scrimMatch->save())
                {
                    return response()->json([
                        'status' => 'success',
                        'message' => "Join scrim success, please wait for scrim master decision",
                    ], 200);
                }
            }
            if (($teamJoin->ranks_id == $isRank) || ($teamJoin->ranks_id == $rankPre) || ($teamJoin->ranks_id == $rankNext))
            {
                $this->scrimMatch->id = Uuid::uuid4()->toString();
                $this->scrimMatch->scrims_id = $scrimOn->id;
                $this->scrimMatch->teams_id = $teamJoin->teams_id;
                $this->scrimMatch->result = 'On Going';
                $this->scrimMatch->round = 'Not yet';
                $this->scrimMatch->status_match = '0';
                if ($this->scrimMatch->save())
                {
                    return response()->json([
                        'status' => 'success',
                        'message' => "Join scrim success, please wait for scrim master decision",
                    ], 200);
                }
            }
            return response()->json([
                'status' => 'error',
                'message' => "Your team rank is not suitable for this scrim"
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getRequestTeamMatch(Request $request,$idScrim)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrimMaster = $this->scrim->where('id','=',$idScrim)
            ->where('games_id','=',$sessGame['game']['id'])
            ->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->first();
            if ($scrimMaster == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->join('teams','scrim_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->join('game_accounts','team_players.game_accounts_id','=','game_accounts.id_game_account')
            ->join('users','game_accounts.users_id','=','users.id')
            ->where('scrim_matches.scrims_id','=',$scrimMaster->id)
            ->where('scrim_matches.result','=','On Going')
            ->select('scrim_matches.id','teams.name as team_name','teams.ranks_id','users.phone')
            ->get();
            if ($scrimMatch->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No request team match',
                    'total_team' => $scrimMatch->count(),
                    'quota' => $scrimMaster->quota,
                    'data' => $scrimMatch
                ], 404);
            }
            foreach ($scrimMatch as $value) {
                $result[] = [
                    'team_name' => $value->team_name,
                    'ranks_class' => $this->rank->where('id','=',$value->ranks_id)->select('class')->first(),
                    'phone' => $value->phone
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get request team match success',
                'total_team' => $scrimMatch->count(),
                'quota' => $scrimMaster->quota,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
