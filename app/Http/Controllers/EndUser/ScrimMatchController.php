<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\Rank;
use App\Models\Team;
use App\Models\Scrim;
use Ramsey\Uuid\Uuid;
use App\Events\JoinScrim;
use App\Events\ScrimLock;
use App\Events\ScrimStart;
use App\Models\ScrimMatch;
use App\Models\TeamPlayer;
use App\Events\ScrimUnlock;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Models\ScrimProgress;
use App\Events\AcceptReqScrim;
use App\Events\ReadyRoomScrim;
use App\Events\RejectReqScrim;
use App\Events\NotReadyRoomScrim;
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
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
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            if ($scrim->status != 'On') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim is not open'
                ], 403);
            }
            if ($scrim->result != 'Prepare') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can not join this scrim, because it is started'
                ], 403);
            }
            $teamCheck = $this->team->join('team_players','team_players.teams_id','=','teams.id')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('teams.games_id','=',$sessGame['game']['id'])
            ->where('team_players.status','=','1')
            ->select('teams.id')
            ->first();
            if ($teamCheck == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not in a team'
                ], 403);
            }
            $alreadyJoin = $this->scrimMatch->join('teams','teams.id','=','scrim_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->where('scrim_matches.scrims_id','=',$idScrim)
            ->where('scrim_matches.teams_id','=',$teamCheck->id)
            ->first();
            if ($alreadyJoin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your team already joined this scrim'
                ], 409);
            }
            $scrimOn = $scrim->where('status','=','On')->first();
            if ($scrimOn == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim is not on'
                ], 404);
            }
            $minRank = $this->rank->min('id');
            $scrimMaster = $this->scrim->where('game_accounts_id','=',$sessGameAccount->id_game_account)->where('id','=',$scrim->id)
            ->first();
            if ($scrimMaster){
                $teamJoin = $this->team->join('team_players', 'teams.id', '=', 'team_players.teams_id')
                ->where('teams.games_id','=',$scrimMaster->games_id)
                ->where('team_players.game_accounts_id','=',$scrimMaster->game_accounts_id)
                ->where('team_players.status','=','1')
                ->first();
                if ($teamJoin == NULL) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You don't have a team"
                    ], 403);
                }
                $isRank = $this->rank->where('id','=',$scrimMaster->ranks_id)->first();
                $rankPre = $this->rank->where('id','<',$scrimMaster->ranks_id)->max('id');
                $rankNext = $this->rank->where('id','>',$scrimMaster->ranks_id)->min('id');
                if (($teamJoin->ranks_id == NULL) && ($scrimMaster->ranks_id == $minRank)) {
                    $this->scrimMatch->id = Uuid::uuid4()->toString();
                    $this->scrimMatch->scrims_id = $scrimMaster->id;
                    $this->scrimMatch->teams_id = $teamJoin->teams_id;
                    $this->scrimMatch->result = 'Ready';
                    $this->scrimMatch->round = '0';
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
                    $this->scrimMatch->result = 'Ready';
                    $this->scrimMatch->round = '0';
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
            $teamJoin = $this->team->join('team_players', 'team_players.teams_id', '=','teams.id' )
                ->where('teams.games_id','=',$scrim->games_id)
                ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
                ->where('team_players.status','=','1')
                ->first();
            if ($teamJoin == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You don't have a team"
                ], 403);
            }
            $isRank = $this->rank->where('id','=',$scrim->ranks_id)->first();
            $rankPre = $this->rank->where('id','<',$scrim->ranks_id)->max('id');
            $rankNext = $this->rank->where('id','>',$scrim->ranks_id)->min('id');
            $scrimMatch = [
                'id' => Uuid::uuid4()->toString(),
                'scrims_id' => $scrim->id,
                'teams_id' => $teamJoin->teams_id,
                'result' => 'Not yet',
                'round' => '0',
                'status_match' => '0',
                'created_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString()
            ];
            if (($teamJoin->ranks_id == NULL) && ($scrim->ranks_id == $minRank))
            {
                event(new JoinScrim($scrimMatch));
                return response()->json([
                    'status' => 'success',
                    'message' => "Join scrim success, please wait for scrim master decision",
                ], 200);
            }
            if (($teamJoin->ranks_id == $isRank) || ($teamJoin->ranks_id == $rankPre) || ($teamJoin->ranks_id == $rankNext))
            {
                event(new JoinScrim($scrimMatch));
                return response()->json([
                    'status' => 'success',
                    'message' => "Join scrim success, please wait for scrim master decision",
                ], 200);
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
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
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->join('scrims','scrims.id','=','scrim_matches.scrims_id')
            ->join('teams','teams.id','=','scrim_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->join('game_accounts','game_accounts.id_game_account','=','team_players.game_accounts_id')
            ->join('users','users.id','=','game_accounts.users_id')
            ->where('scrim_matches.scrims_id','=',$scrimMaster->id)
            ->where('scrim_matches.status_match','=','0')
            ->where('team_players.role_team','=','Master')
            ->select('scrim_matches.id',
            'scrim_matches.scrims_id',
            'scrim_matches.teams_id',
            'scrims.name_party',
            'teams.name as team_name',
            'teams.ranks_id',
            'users.phone',
            'scrim_matches.status_match',
            'scrim_matches.result as status_ready',
            'scrims.result as status_scrim',
            )
            ->get();
            if ($scrimMatch->count() < '1') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No request team match',
                    'total_team' => $scrimMatch->count(),
                    'quota' => $scrimMaster->quota,
                    'name_party' => $scrimMaster->name_party,
                    'data' => $scrimMatch
                ], 404);
            }
            foreach ($scrimMatch as $value) {
                $result[] = [
                    'id' => $value->id,
                    'scrims_id' => $value->scrims_id,
                    'teams_id' => $value->teams_id,
                    'team_name' => $value->team_name,
                    'ranks_class' => $this->rank->where('id','=',$value->ranks_id)->select('class')->first(),
                    'phone' => $value->phone,
                    'status_match' => $value->status_match,
                    'status_room' => $value->status_scrim,
                    'status_ready' => $value->status_ready
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get request team match success',
                'total_team' => $scrimMatch->count(),
                'quota' => $scrimMaster->quota,
                'name_party' => $scrimMaster->name_party,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptRequestTeamMatch(Request $request,$idScrim,$idMatch)//for Master Scrim
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMaster = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->where('game_accounts_id','=',$sessGameAccount->id_game_account)->first();
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $alreadyAccept = $this->scrimMatch->where('id','=',$idMatch)->where('scrims_id','=',$scrimMaster->id)->where('status_match','=','1')->first();
            if ($alreadyAccept != NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This team match already accepted'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->where('id','=',$idMatch)->where('scrims_id','=',$scrim->id)->first();
            if ($scrimMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match request not found'
                ], 404);
            }
            event(new AcceptReqScrim($scrimMatch));
            return response()->json([
                'status' => 'success',
                'message' => 'Accept team match success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectRequestTeamMatch(Request $request,$idScrim,$idMatch)//for Master Scrim
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id', '=', $idScrim)->where('games_id', '=', $sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMaster = $this->scrim->where('id','=',$scrim->id)->where('games_id','=',$scrim->games_id)->where('game_accounts_id', '=', $sessGameAccount->id_game_account)->first();
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->where('id', '=', $idMatch)->where('scrims_id', '=', $scrim->id)->first();
            if ($scrimMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request team not found'
                ], 404);
            }
            event(new RejectReqScrim($scrimMatch));

            return response()->json([
                'status' => 'success',
                'message' => 'Reject request team match success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function lockMatchScrim(Request $request,$idScrim)//for Master Scrim
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMaster = $this->scrim->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('id','=',$scrim->id)
            ->where('games_id','=',$scrim->games_id)->first();
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->where('scrims_id','=',$scrim->id)->where('status_match','=','1')->get();
            if ($scrimMatch->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $scrimMatchReady = $this->scrimMatch->where('scrims_id','=',$scrim->id)->where('status_match','=','1')->where('result','=','Ready')->get();
            if ($scrimMatchReady->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Every team match must be ready'
                ], 403);
            }
            if ($scrimMatchReady->count() != $scrim->quota) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim must have at least '.$scrim->quota.' team match'
                ], 403);
            }
            event(new ScrimLock($scrimMaster));

            return response()->json([
                'status' => 'success',
                'message' => 'Room has been locked'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function unlockMatchScrim(Request $request,$idScrim)//for Master Scrim
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id', '=', $idScrim)->where('games_id', '=', $sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMaster = $this->scrim->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('id','=',$scrim->id)
            ->where('games_id','=',$scrim->games_id)->first();
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            if ($scrimMaster->result == 'Battle') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim has been battle'
                ], 403);
            }
            event(new ScrimUnlock($scrimMaster));

            return response()->json([
                'status' => 'success',
                'message' => 'Room has been unlocked'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function startMatchScrim(Request $request,$idScrim)//for Master Scrim
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMaster = $this->scrim->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('id','=',$scrim->id)
            ->where('games_id','=',$scrim->games_id)->first();
            if ($scrimMaster == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not scrim master'
                ], 403);
            }
            $scrimMatch = $this->scrimMatch->where('scrims_id','=',$scrim->id)->where('status_match','=','1')
            ->where('result','=','Ready')
            ->get();
            if ($scrimMatch->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            foreach ($scrimMatch as $match) {
                $match->result = 'On Going';
                $match->round = '1';
                $match->save();
            }
            $scrimLock = $this->scrim->where('id','=',$scrim->id)
            ->where('games_id','=',$scrim->games_id)
            ->where('result','=','Lock')->first();
            if ($scrimLock == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Room must be locked'
                ], 403);
            }
            event(new ScrimStart($scrimLock));

            return response()->json([
                'status' => 'success',
                'message' => 'Room has been started'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTeamMatchScrim(Request $request,$idScrim)//for Member User
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your role is not allowed to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $scrimMatch = $this->scrimMatch->join('scrims','scrims.id','=','scrim_matches.scrims_id')
            ->join('teams','scrim_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->join('game_accounts','team_players.game_accounts_id','=','game_accounts.id_game_account')
            ->join('users','game_accounts.users_id','=','users.id')
            ->where('scrim_matches.scrims_id','=',$scrim->id)
            ->where('scrim_matches.status_match','=','1')
            ->where('team_players.role_team','=','Master')
            ->select('scrim_matches.id','scrim_matches.scrims_id','scrim_matches.teams_id','scrims.name_party','teams.name as team_name','teams.ranks_id','users.phone','scrim_matches.status_match','scrim_matches.result as status_ready','scrims.result as status_scrim')
            ->get();
            if ($scrimMatch->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No team match',
                    'total_team' => $scrimMatch->count(),
                    'quota' => $scrim->quota,
                    'name_party' => $scrim->name_party,
                    'data' => $scrimMatch
                ], 404);
            }
            foreach ($scrimMatch as $value) {
                $result[] = [
                    'id' => $value->id,
                    'scrims_id' => $value->scrims_id,
                    'teams_id' => $value->teams_id,
                    'team_name' => $value->team_name,
                    'ranks_class' => $this->rank->where('id','=',$value->ranks_id)->select('class')->first(),
                    'phone' => $value->phone,
                    'status_match' => $value->status_match,
                    'status_room' => $value->status_scrim,
                    'status_ready' => $value->status_ready
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Team match found',
                'total_team' => $scrimMatch->count(),
                'quota' => $scrim->quota,
                'name_party' => $scrim->name_party,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function readyToPlay(Request $request,$idScrim)//for Member User
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $teamMatch = $this->scrimMatch->join('scrims','scrims.id','=','scrim_matches.scrims_id')
            ->join('teams','scrim_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->where('scrim_matches.scrims_id','=',$scrim->id)
            ->where('scrim_matches.status_match','=','1')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->select('scrim_matches.id','scrim_matches.scrims_id','scrims.name_party','teams.name as team_name')
            ->first();
            if ($teamMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $scrimMatch = $this->scrimMatch->where('id','=',$teamMatch->id)->first();
            if ($scrimMatch->result == 'Ready') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match has ready'
                ], 409);
            }
            event(new ReadyRoomScrim($scrimMatch));

            return response()->json([
                'status' => 'success',
                'message' => 'Team match ready'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function notReadyToPlay(Request $request,$idScrim)//for Member User
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
            if (($sessGame == NULL) || ($sessGameAccount == NULL)) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id','=',$idScrim)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($scrim == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $teamMatch = $this->scrimMatch->join('scrims','scrims.id','=','scrim_matches.scrims_id')
            ->join('teams','scrim_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->where('scrim_matches.scrims_id','=',$scrim->id)
            ->where('scrim_matches.status_match','=','1')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->select('scrim_matches.id','scrim_matches.scrims_id','scrims.name_party','teams.name as team_name')
            ->first();
            if ($teamMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $scrimMatch = $this->scrimMatch->where('id','=',$teamMatch->id)->first();
            if ($scrimMatch->result == 'Not yet') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match has not ready'
                ], 409);
            }
            event(new NotReadyRoomScrim($scrimMatch));

            return response()->json([
                'status' => 'success',
                'message' => 'Team match not ready'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getBracketScrim(Request $request,$idScrim)//for Member User
    {
        try {
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
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $scrim = $this->scrim->where('id', '=', $idScrim)->where('games_id', '=', $sessGame['game']['id'])->first();
            if ($scrim == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $teamMatch = $this->scrimMatch->join('scrims', 'scrims.id', '=', 'scrim_matches.scrims_id')
            ->join('teams', 'scrim_matches.teams_id', '=', 'teams.id')
            ->join('team_players', 'teams.id', '=', 'team_players.teams_id')
            ->where('scrims.games_id', '=', $sessGame['game']['id'])
            ->where('scrim_matches.scrims_id', '=', $scrim->id)
            ->where('scrim_matches.status_match', '=', '1')
            ->where('scrim_matches.result', '!=', 'Not yet')
            ->where('team_players.status', '=', '1')
            ->select('scrim_matches.id', 'scrim_matches.teams_id','teams.name as team_name','scrim_matches.round','scrim_matches.result')
            ->get();
            if ($teamMatch->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $round=0;
            while(count($teamMatch)>1)
            {
                $round++;  // Increment our round
                $tables=array();  // Clear our tables
                $index=0;
                while(count($tables) < floor(count($teamMatch)/2))  // want an even amount of tables
                    $tables[]=array($teamMatch[$index++],$teamMatch[$index++]);
                // if($index<count($teamMatch)){// extra team, add to tables, but no opposing team
                //     $tables[]=array($teamMatch[$index++],null);
                // }
                $teamMatch=array(); // clear out next round participants
                // foreach($tables as $idx=>$table)
                // {
                //     $tbl=$idx+1;
                //     if($table[1]===NULL)  // extra team advances to next level automatically
                //     {
                //         $result[] = [
                //             'id_scrim' => $table[0]['scrims_id'],
                //             'round' => $round,
                //             'team1' => $table[0]['team_name'],
                //             'team2' => '',
                //             'result' => '',
                //         ];
                //         $winner=0;
                //     } else  {
                //         $result[] = [
                //             'id_scrim' => $table[0]['scrims_id'],
                //             'round' => $round,
                //             'team1' => $table[0]['team_name'],
                //             'team2' => $table[1]['team_name'],
                //             'result' => '',
                //         ];
                //         $winner=rand(0,1);    // Generate a winner
                //     }
                //     $teamMatch[]=$table[$winner];  // Add WInnerto next round
                // }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Scheme bracket',
                'id_scrim' => $scrim->id,
                'name_party' => $scrim->name_party,
                'data' => $tables,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
