<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\Rank;
use App\Models\Team;
use Ramsey\Uuid\Uuid;
use App\Models\TeamPlayer;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Events\JoinTournament;
use App\Events\TournamentLock;
use App\Events\TournamentStart;
use App\Models\TournamentMatch;
use App\Events\TournamentUnlock;
use App\Events\AcceptReqTournament;
use App\Events\ReadyRoomTournament;
use App\Events\RejectReqTournament;
use App\Http\Controllers\Controller;
use App\Events\NotReadyRoomTournament;

class TournamentMatchController extends Controller
{
    public function __construct()
    {
        $this->tournament = new Tournament();
        $this->tournamentMatch = new TournamentMatch();
        $this->team = new Team();
        $this->gameAccount = new GameAccount();
        $this->rank = new Rank();
        $this->teamPlayer = new TeamPlayer();
        $this->eo = new EoTournament();
    }
    public function joinRoom(Request $request,$idTournament)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            if ($tournament->result != 'Prepare') {
                return response()->json([
                    "status" => "error",
                    "message" => "You can't join this tournament, it's already started"
                ], 403);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournament_eos.game_accounts_id','tournaments.id','tournaments.games_id')
            ->first();
            if ($eo != NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "You are an EO, your team can't join a tournament"
                ], 403);
            }
            if ($tournament->result != 'Prepare') {
                return response()->json([
                    "status" => "error",
                    "message" => "You can't join a tournament that has already started"
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
            $eoTeam = $this->eo->join('tournaments','tournaments.eo_id','=','tournament_eos.id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->select('tournament_eos.game_accounts_id')
            ->first();
            if ($eoTeam != NULL) {
                $memberTeam = $this->teamPlayer->where('game_accounts_id','=',$eoTeam->game_accounts_id)
                ->where('status','=','1')
                ->select('teams_id')
                ->first();
                if ($teamCheck->id == $memberTeam->teams_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "You can't join cause one of your team member is an Tournament EO"
                    ], 403);
                }
            }

            $alreadyJoin = $this->tournamentMatch->join('teams','teams.id','=','tournament_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->where('tournament_matches.tournaments_id','=',$idTournament)
            ->where('tournament_matches.teams_id','=',$teamCheck->id)
            ->first();
            if ($alreadyJoin != NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "You already join this tournament"
                ], 409);
            }
            $minRank = $this->rank->min('id');
            $teamJoin = $this->team->join('team_players','team_players.teams_id','=','teams.id')
            ->where('teams.games_id','=',$tournament->games_id)
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->first();
            if ($teamJoin == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "You don't have a team"
                ], 403);
            }
            $isRank = $this->rank->where('id','=',$tournament->ranks_id)->first();
            $rankPre = $this->rank->where('id','<',$tournament->ranks_id)->max('id');
            $rankNext = $this->rank->where('id','>',$tournament->ranks_id)->min('id');
            $tournamentMatch = [
                'id' => Uuid::uuid4()->toString(),
                'tournaments_id' => $tournament->id,
                'teams_id' => $teamJoin->teams_id,
                'result' => 'Not yet',
                'round' => 'Not yet',
                'status_match' => '0',
                'created_at' => Carbon::now('Asia/Jakarta')->toDateTimeString(),
                'updated_at' => Carbon::now('Asia/Jakarta')->toDateTimeString()
            ];
            if (($teamJoin->ranks_id == NULL) && ($tournament->ranks_id == $minRank)) {
                event(new JoinTournament($tournamentMatch));

                return response()->json([
                    "status" => "success",
                    "message" => "You join this tournament, please wait for tournament eo decision"
                ], 200);
            }
            if (($teamJoin->ranks_id == $isRank) || ($teamJoin->ranks_id == $rankPre) || ($teamJoin->ranks_id == $rankNext)) {
                event(new JoinTournament($tournamentMatch));

                return response()->json([
                    "status" => "success",
                    "message" => "You join this tournament, please wait for tournament eo decision"
                ], 200);
            }
            return response()->json([
                'status' => 'error',
                'message' => "Your team rank is not suitable for this tournament"
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getRequestTeamMatch(Request $request,$idTournament)
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
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$idTournament)
            ->where('tournaments.games_id','=',$sessGame['game']['id'])
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id','tournaments.quota','tournaments.name_tournament')
            ->first();
            if($eo == NULL){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            $requestTeam = $this->tournamentMatch->join('tournaments','tournaments.id','=','tournament_matches.tournaments_id')
            ->join('teams','teams.id','=','tournament_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->join('game_accounts','game_accounts.id_game_account','=','team_players.game_accounts_id')
            ->join('users','users.id','=','game_accounts.users_id')
            ->where('tournaments.id','=',$eo->id)
            ->where('tournament_matches.status_match','=','0')
            ->where('team_players.role_team','=','Master')
            ->select('tournament_matches.id',
            'tournament_matches.tournaments_id',
            'tournament_matches.teams_id',
            'tournaments.name_tournament',
            'teams.name as team_name',
            'teams.ranks_id',
            'users.phone',
            'tournament_matches.status_match',
            'tournament_matches.result as status_ready',
            'tournaments.result as status_tournament',
            )->get();
            if ($requestTeam->count() < 1) {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no request team",
                    "total_team" => $requestTeam->count(),
                    "quota" => $eo->quota,
                    'name_tournament' => $eo->name_tournament,
                    "data" => $requestTeam
                ], 404);
            }
            foreach ($requestTeam as $value) {
                $result[] = [
                    'id' => $value->id,
                    'tournaments_id' => $value->tournaments_id,
                    'teams_id' => $value->teams_id,
                    'team_name' => $value->team_name,
                    'ranks_class' => $this->rank->where('id','=',$value->ranks_id)->select('class')->first(),
                    'phone' => $value->phone,
                    'status_match' => $value->status_match,
                    'status_ready' => $value->status_ready,
                    'status_tournament' => $value->status_tournament,
                ];
            }
            return response()->json([
                "status" => "success",
                "message" => "Get request team success",
                "total_team" => $requestTeam->count(),
                "quota" => $eo->quota,
                'name_tournament' => $eo->name_tournament,
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTeamMatchTournament(Request $request,$idTournament)//for Member User
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
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $tournamentMatch = $this->tournamentMatch->join('tournaments','tournaments.id','=','tournament_matches.tournaments_id')
            ->join('teams','teams.id','=','tournament_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->join('game_accounts','game_accounts.id_game_account','=','team_players.game_accounts_id')
            ->join('users','users.id','=','game_accounts.users_id')
            ->where('tournament_matches.tournaments_id','=',$tournament->id)
            ->where('tournament_matches.status_match','=','1')
            ->where('team_players.role_team','=','Master')
            ->select('tournament_matches.id',
            'tournament_matches.tournaments_id',
            'tournament_matches.teams_id',
            'tournaments.name_tournament',
            'teams.name as team_name',
            'teams.ranks_id',
            'users.phone',
            'tournament_matches.status_match',
            'tournament_matches.result as status_ready',
            'tournaments.result as status_tournament',
            )->get();
            if ($tournamentMatch->count() == 0) {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no team match",
                    "total_team" => $tournamentMatch->count(),
                    "quota" => $tournament->quota,
                    'name_tournament' => $tournament->name_tournament,
                    "data" => $tournamentMatch
                ], 404);
            }
            foreach ($tournamentMatch as $value) {
                $result[] = [
                    'id' => $value->id,
                    'tournaments_id' => $value->tournaments_id,
                    'teams_id' => $value->teams_id,
                    'team_name' => $value->team_name,
                    'ranks_class' => $this->rank->where('id','=',$value->ranks_id)->select('class')->first(),
                    'phone' => $value->phone,
                    'status_match' => $value->status_match,
                    'status_ready' => $value->status_ready,
                    'status_tournament' => $value->status_tournament,
                ];
            }
            return response()->json([
                "status" => "success",
                "message" => "Get team match success",
                "total_team" => $tournamentMatch->count(),
                "quota" => $tournament->quota,
                'name_tournament' => $tournament->name_tournament,
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptRequestTeamMatch(Request $request,$idTournament,$idMatch)
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
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id')
            ->first();
            if($eo == NULL){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            $alreadyAccept = $this->tournamentMatch->where('id','=',$idMatch)
            ->where('tournaments_id','=',$eo->id)
            ->where('status_match','=','1')
            ->first();
            if ($alreadyAccept != NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "This team match already accepted"
                ], 403);
            }
            $requestTeam = $this->tournamentMatch->where('id','=',$idMatch)
            ->where('tournaments_id','=',$eo->id)->first();
            if ($requestTeam == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Team match request not found"
                ], 404);
            }
            event(new AcceptReqTournament($requestTeam));

            return response()->json([
                "status" => "success",
                "message" => "Accept request team success"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectRequestTeamMatch(Request $request,$idTournament,$idMatch)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "Your role is not allowed to access this resource"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id')
            ->first();
            if($eo == NULL){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            $tournamentMatch = $this->tournamentMatch->where('id','=',$idMatch)
            ->where('tournaments_id','=',$eo->id)->first();
            if ($tournamentMatch == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Request team not found"
                ], 404);
            }
            event(new RejectReqTournament($tournamentMatch));

            return response()->json([
                "status" => "success",
                "message" => "Reject request team match success"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function lockMatchTournament(Request $request,$idTournament)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "Your role is not allowed to access this resource"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id')
            ->first();
            if(!$eo){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            $tournamentMatch = $this->tournamentMatch->where('tournaments_id','=',$eo->id)
            ->where('status_match','=','1')
            ->get();
            if ($tournamentMatch->count() < 1) {
                return response()->json([
                    "status" => "error",
                    "message" => "Team match not found"
                ], 404);
            }
            $teamReady = $this->tournamentMatch->where('tournaments_id','=',$eo->id)->where('status_match','=','1')->where('result','=','Ready')->get();
            if ($teamReady->count() < 1) {
                return response()->json([
                    "status" => "error",
                    "message" => "Every team must be ready"
                ], 403);
            }
            if ($teamReady()->count() != $tournament->quota) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament must have at least ".$tournament->quota." teams"
                ], 403);
            }
            event(new TournamentLock($tournament));

            return response()->json([
                "status" => "success",
                "message" => "Tournament has been locked"
            ], 200);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function unlockMatchTournament(Request $request,$idTournament)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "Your role is not allowed to access this resource"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id')
            ->first();
            if($eo == NULL){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            if ($tournament->result == 'Battle') {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament has been battle"
                ], 403);
            }
            event(new TournamentUnlock($tournament));

            return response()->json([
                "status" => "success",
                "message" => "Tournament has been unlocked"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function startMatchTournament(Request $request, $idTournament)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "Your role is not allowed to access this resource"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == NULL || $sessGameAccount == NULL) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $this->tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournaments.id','=',$tournament->id)
            ->where('tournaments.games_id','=',$tournament->games_id)
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->select('tournaments.id')
            ->first();
            if($eo == NULL){
                return response()->json([
                    "status" => "error",
                    "message" => "You are not an EO"
                ], 403);
            }
            $tournamentMatch = $this->tournamentMatch->where('tournaments_id','=',$eo->id)
            ->where('status_match','=','1')
            ->where('result','=','Ready')
            ->get();
            if ($tournamentMatch->count() < 1) {
                return response()->json([
                    "status" => "error",
                    "message" => "Team match not found"
                ], 404);
            }
            foreach ($tournamentMatch as $match) {
                $match->result = 'On Going';
                $match->save();
            }
            $tournamentLock = $this->tournament->where('id','=',$tournament->id)
            ->where('games_id','=',$tournament->games_id)
            ->where('result','=','Lock')->first();
            if ($tournamentLock == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Room must be locked"
                ], 403);
            }
            event(new TournamentStart($tournamentLock));

            return response()->json([
                "status" => "success",
                "message" => "Tournament has been started"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function readyToPlay(Request $request, $idTournament)//for Member User
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    "status" => "error",
                    "message" => "Your role is not allowed to access this resource"
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id', auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $teamMatch = $this->tournamentMatch->join('tournaments','tournaments.id','=','tournament_matches.tournaments_id')
            ->join('teams','tournament_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->where('tournament_matches.tournaments_id','=',$tournament->id)
            ->where('tournament_matches.status_match','=','1')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->select('tournament_matches.id','tournament_matches.tournaments_id','tournaments.name_tournament','teams.name as team_name')
            ->first();
            if ($teamMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $tournamentMatch = $this->tournamentMatch->where('id','=',$teamMatch->id)->first();
            if ($tournamentMatch->result == 'Ready') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match has ready'
                ], 409);
            }
            event(new ReadyRoomTournament($tournamentMatch));

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
    public function notReadyToPlay(Request $request,$idTournament)//for Member User
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
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $teamMatch = $this->tournamentMatch->join('tournaments','tournaments.id','=','tournament_matches.tournaments_id')
            ->join('teams','tournament_matches.teams_id','=','teams.id')
            ->join('team_players','teams.id','=','team_players.teams_id')
            ->where('tournament_matches.tournaments_id','=',$tournament->id)
            ->where('tournament_matches.status_match','=','1')
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->select('tournament_matches.id','tournament_matches.tournaments_id','tournaments.name_tournament','teams.name as team_name')
            ->first();
            if ($teamMatch == NULL) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match not found'
                ], 404);
            }
            $tournamentMatch = $this->tournamentMatch->where('id','=',$teamMatch->id)->first();
            if ($tournamentMatch->result == 'Not yet') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team match has not ready'
                ], 409);
            }
            event(new NotReadyRoomTournament($tournamentMatch));

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
}
