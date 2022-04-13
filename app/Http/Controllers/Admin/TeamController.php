<?php

namespace App\Http\Controllers\Admin;

use App\Models\Rank;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->team = new Team();
        $this->rank = new Rank();
        $this->teamPlayer = new TeamPlayer();
        $this->gameAccount = new GameAccount();
    }
    public function getTeams(Request $request, $idGame)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $dataTeam = $this->team->join('games', 'games.id', '=', 'teams.games_id')->where('games.id', $idGame)->select('teams.*', 'games.name as game_name')->get();
            if ($dataTeam->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found.'
                ], 404);
            }
            foreach ($dataTeam as $value) {
                $result[] = [
                    'id' => $value->id,
                    'games_id' => $value->games_id,
                    'game_name' => $value->game_name,
                    'rank_class' => $this->rank->select('class')->where('id', $value->ranks_id)->first(),
                    'name' => $value->name,
                    'logo' => URL::to('/api/picture-team/'.$value->logo),
                    'won' => $value->won,
                    'lose' => $value->lose,
                    'point' => $value->point,
                    'total_match_scrim' => $value->total_match_scrim,
                    'total_match_tournament' => $value->total_match_tournament,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully get data.',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTeamDetail(Request $request, $idGame, $idTeam)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $dataTeam = $this->team->join('games', 'games.id', '=', 'teams.games_id')
            ->where('teams.id', '=', $idTeam)
            ->where('games.id', '=' ,$idGame)
            ->select('teams.*', 'games.name as game_name')->first();
            if ($dataTeam == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found.'
                ], 404);
            }

            $memberTeam = $this->teamPlayer->join('teams', 'teams.id', '=', 'team_players.teams_id')
            ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('users', 'users.id', '=', 'game_accounts.users_id')
            ->where('team_players.teams_id','=', $idTeam)
            ->where('team_players.status', '=', '1')
            ->select('team_players.*',
            'game_accounts.nickname',
            'users.name',
            'users.phone',
            'users.email',
            'users.provinsi',
            'users.kabupaten',
            'users.kecamatan',
            'users.avatar')->get();
            foreach ($memberTeam as $value) {
                $resultMember[] = [
                    'game_accounts_id' => $value->game_accounts_id,
                    'nickname' => $value->nickname,
                    'role_team' => $value->role_team,
                    'name' => $value->name,
                    'phone' => $value->phone,
                    'email' => $value->email,
                    'provinsi' => $value->provinsi,
                    'kabupaten' => $value->kabupaten,
                    'kecamatan' => $value->kecamatan,
                    'avatar' => $value->avatar,
                ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully get data.',
                'data' => [
                    'team' => [
                        'id' => $dataTeam->id,
                        'games_id' => $dataTeam->games_id,
                        'game_name' => $dataTeam->game_name,
                        'rank_class' => $this->rank->select('class')->where('id', $dataTeam->ranks_id)->first(),
                        'name' => $dataTeam->name,
                        'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                        'won' => $dataTeam->won,
                        'lose' => $dataTeam->lose,
                        'point' => $dataTeam->point,
                        'total_match_scrim' => $dataTeam->total_match_scrim,
                        'total_match_tournament' => $dataTeam->total_match_tournament,
                        'created_at' => $dataTeam->created_at,
                        'updated_at' => $dataTeam->updated_at
                    ],
                    'member' => $resultMember
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
