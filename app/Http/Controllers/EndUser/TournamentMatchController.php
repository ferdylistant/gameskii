<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Team;
use Ramsey\Uuid\Uuid;
use App\Models\TeamPlayer;
use App\Models\Tournament;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use App\Models\TournamentMatch;
use App\Http\Controllers\Controller;

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
            if ($sessGame == null || $sessGameAccount == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "Session time out"
                ], 408);
            }
            $tournament = $this->tournament->where('id','=',$idTournament)->where('games_id','=',$sessGame['game']['id'])->first();
            if ($tournament == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tournament not found"
                ], 404);
            }
            $eo = $tournament->join('tournament_eos','tournament_eos.id','=','tournaments.eo_id')
            ->where('tournament_eos.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->first();
            if ($eo != null) {
                return response()->json([
                    "status" => "error",
                    "message" => "You are an EO, you can't join a tournament"
                ], 403);
            }
            if ($tournament->result != 'Prepare') {
                return response()->json([
                    "status" => "error",
                    "message" => "You can't join a tournament that has already started"
                ], 403);
            }
            $alreadyJoin = $this->tournamentMatch->join('teams','teams.id','=','tournament_matches.teams_id')
            ->join('team_players','team_players.teams_id','=','teams.id')
            ->where('tournament_matches.tournaments_id','=',$idTournament)
            ->where('team_players.game_accounts_id','=',$sessGameAccount->id_game_account)
            ->where('team_players.status','=','1')
            ->first();
            if ($alreadyJoin != null) {
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
            if ($teamJoin == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "You don't have a team"
                ], 403);
            }
            $isRank = $this->rank->where('id','=',$tournament->ranks_id)->first();
            $rankPre = $this->rank->where('id','<',$tournament->ranks_id)->max('id');
            $rankNext = $this->rank->where('id','>',$tournament->ranks_id)->min('id');
            if (($teamJoin->ranks_id == null) && ($scrimOn->ranks_id == $minRank)) {
                $this->tournamentMatch->id = Uuid::uuid4()->toString();
                $this->tournamentMatch->tournaments_id = $tournament->id;
                $this->tournamentMatch->teams_id = $teamJoin->teams_id;
                $this->tournamentMatch->result = 'Not yet';
                $this->tournamentMatch->round = 'Not yet';
                $this->tournamentMatch->status_match = '0';
                if ($this->tournamentMatch->save()) {
                    return response()->json([
                        "status" => "success",
                        "message" => "You join this tournament, please wait for tournament eo decision"
                    ], 200);
                }
            }
            if (($teamJoin->ranks_id == $isRank) || ($teamJoin->ranks_id == $rankPre) || ($teamJoin->ranks_id == $rankNext)) {
                $this->tournamentMatch->id = Uuid::uuid4()->toString();
                $this->tournamentMatch->tournaments_id = $tournament->id;
                $this->tournamentMatch->teams_id = $teamJoin->teams_id;
                $this->tournamentMatch->result = 'Not yet';
                $this->tournamentMatch->round = 'Not yet';
                $this->tournamentMatch->status_match = '0';
                if ($this->tournamentMatch->save()) {
                    return response()->json([
                        "status" => "success",
                        "message" => "You join this tournament, please wait for tournament eo decision"
                    ], 200);
                }
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
}
