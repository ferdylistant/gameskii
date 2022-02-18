<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use App\Models\Team;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\TeamPlayer;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Notifications\TeamNotification;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function __construct()
    {
        $this->team = new Team();
        $this->teamPlayer = new TeamPlayer();
        $this->follow = new SocialFollow();
        $this->gameAccount = new GameAccount();
        $this->rank = new Rank();
        $this->user = new User();
    }

    public function getTeam(Request $request)
    {
        $data = $this->team->all();
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Get all team',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function createTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teams',
            'logo' => 'required|file|max:5048|image',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }
        try {
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($request->hasFile('logo')) {
                $dataFile = $request->file('logo');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $dataFile->move(storage_path('uploads/picture-team'), $imageName);
                $this->team->logo = $imageName;
            }
            $this->team->id = Uuid::uuid4()->toString();
            $this->team->games_id = $sessGame['game']['id'];
            $this->team->name = $request->name;
            if ($this->team->save()) {
                $this->teamPlayer->id = Uuid::uuid4()->toString();
                $this->teamPlayer->teams_id = $this->team->id;
                $this->teamPlayer->game_accounts_id = $sessGameAccount->id_game_account;
                $this->teamPlayer->role_team = 'Master';
                $this->teamPlayer->status = '1';
                $this->teamPlayer->save();
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Team created successfully!',
                    'data' => [
                        'team' => [
                            'id' => $this->team->id,
                            'games_id' => $this->team->games_id,
                            'name' => $this->team->name,
                            'logo' => URL::to('/api/picture-team/'.$this->team->logo),
                        ],
                        'member-team' => [
                            'game_accounts_id' => $this->teamPlayer->game_accounts_id,
                            'role_team' => $this->teamPlayer->role_team,
                            'status' => $this->teamPlayer->status,
                        ]
                    ]
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
    public function addMembers(Request $request, $idTeam, $idGameAccount)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        // return response()->json($sessGame);
        $sessGameAccount = $request->session()->get('game_account');
        $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        if (!$gameAccount) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Game account not found!'
            ], 404);
        }
        $dataFollow =  $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('acc_following_id','=', $gameAccount->id)
        ->where('status_follow','=', '1')
        ->first();
        if (!$dataFollow) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => "You're not be friend with this account!"
            ], 404);
        }
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataTeamPlayer = $this->teamPlayer->where('teams_id', '=', $idTeam)
        ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('role_team', '=', 'Master')
        ->first();
        if (!$dataTeamPlayer) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'You are not master of this team!'
            ], 404);
        }
        $dataMaster = $this->gameAccount->where('id_game_account', $dataTeamPlayer->game_accounts_id)->first();
        // $ranks = $this->rank->where('id', '=', $dataTeam->ranks_id)->first();
        $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.status','=','1')->get(['game_accounts.id_game_account', 'game_accounts.nickname','team_players.role_team']);
        foreach ($dataPlayers as $value) {
            $resultDataPlayers[] = $value;
        }
        try {
            $details = [
                'id' => $idTeam,
                'games_id' => $sessGame['game']['id'],
                'name' => $dataTeam->name,
                'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                'ranks_id' => $dataTeam->ranks_id,
                'won' => $dataTeam->won,
                'lose' => $dataTeam->lose,
                'total_match_scrim' => $dataTeam->total_match_scrim,
                'total_match_tournament' => $dataTeam->total_match_tournament,
                'point' => $dataTeam->point,
                'master' => [
                    'id' => $dataTeamPlayer->id,
                    'game_accounts_id' => $dataTeamPlayer->game_accounts_id,
                    'nickname' => $dataMaster->nickname,
                    'role_team' => $dataTeamPlayer->role_team,
                    'status' => $dataTeamPlayer->status,
                ],
                'created_at' => $dataTeam->created_at,
                'message' => $dataMaster->nickname.' invited you to join '.$dataTeam->name.' team!',
                'game' => [
                    'id' => $sessGame['game']['id'],
                    'name' => $sessGame['game']['name'],
                    'logo' => $sessGame['game']['picture'],
                ],
                'member-team' => $resultDataPlayers,
            ];
            $user = $this->user->where('id', $gameAccount->users_id)->first();
            $this->teamPlayer = new TeamPlayer();
            $this->teamPlayer->id = Uuid::uuid4()->toString();
            $this->teamPlayer->teams_id = $idTeam;
            $this->teamPlayer->game_accounts_id = $idGameAccount;
            $this->teamPlayer->role_team = 'Member';
            $this->teamPlayer->status = '0';
            $this->teamPlayer->save();
            $user->notify(new TeamNotification($details));
            return response()->json([
                'code' => 201,
                'status' => 'success',
                'message' => 'Member added successfully!',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptInvitation(Request $request, $idTeam)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        // $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataMaster = $this->teamPlayer->where('teams_id', '=', $idTeam)->where('status','=','1')
        ->where('role_team','=','Master')
        ->first();
        if (!$dataMaster) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Master not found!'
            ], 404);
        }
        $dataGameAccountMaster = $this->gameAccount->where('id_game_account', $dataMaster->game_accounts_id)->first();
        $dataTeamPlayer = $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('teams_id', '=', $idTeam)
        ->first();
        if (!$dataTeamPlayer) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Nothing to accept!'
            ], 404);
        }

        try {
            $dataTeamPlayer->status = '1';
            if ($dataTeamPlayer->save()){
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')->get(['game_accounts.id_game_account', 'game_accounts.nickname','team_players.role_team']);
                foreach ($dataPlayers as $value) {
                    $resultDataPlayers[] = $value;
                }
                $details = [
                    'id' => $idTeam,
                    'games_id' => $sessGame['game']['id'],
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => [
                        'id' => $dataMaster->id,
                        'game_accounts_id' => $dataMaster->game_accounts_id,
                        'nickname' => $dataGameAccountMaster->nickname,
                        'role_team' => $dataMaster->role_team,
                        'status' => $dataMaster->status,
                    ],
                    'created_at' => $dataTeam->created_at,
                    'message' => $sessGameAccount->nickname.' accepted your invitation to join '.$dataTeam->name.' team!',
                    'game' => [
                        'id' => $sessGame['game']['id'],
                        'name' => $sessGame['game']['name'],
                        'logo' => $sessGame['game']['picture'],
                    ],
                    'member-team' => $resultDataPlayers,
                ];
                $user = $this->user->where('id', $dataGameAccountMaster->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'You accepted the invitation!',
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

    }
     public function rejectInvitation(Request $request, $idTeam)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        // $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataMaster = $this->teamPlayer->where('teams_id', '=', $idTeam)->where('status', '=', '1')
        ->where('role_team', '=', 'Master')
        ->first();
        if (!$dataMaster) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Master not found!'
            ], 404);
        }
        $dataGameAccountMaster = $this->gameAccount->where('id_game_account', $dataMaster->game_accounts_id)->first();
        $dataTeamPlayer = $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('teams_id', '=', $idTeam)
        ->first();
        if (!$dataTeamPlayer) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Nothing to reject!'
            ], 404);
        }

        try {
            $dataTeamPlayer->status = '2';
            if ($dataTeamPlayer->save()) {
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')->get(['game_accounts.id_game_account', 'game_accounts.nickname','team_players.role_team']);
                foreach ($dataPlayers as $value) {
                    $resultDataPlayers[] = $value;
                }
                $details = [
                    'id' => $idTeam,
                    'games_id' => $sessGame['game']['id'],
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => [
                        'id' => $dataMaster->id,
                        'game_accounts_id' => $dataMaster->game_accounts_id,
                        'nickname' => $dataGameAccountMaster->nickname,
                        'role_team' => $dataMaster->role_team,
                        'status' => $dataMaster->status,
                    ],
                    'created_at' => $dataTeam->created_at,
                    'message' => $sessGameAccount->nickname.' rejected your invitation to join '.$dataTeam->name.' team!',
                    'game' => [
                        'id' => $sessGame['game']['id'],
                        'name' => $sessGame['game']['name'],
                        'logo' => $sessGame['game']['picture'],
                    ],
                    'member-team' => $resultDataPlayers,
                ];
                $user = $this->user->where('id', $dataGameAccountMaster->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'You rejected the invitation!',
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function joinTeam(Request $request, $idTeam)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        // $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataMaster = $this->teamPlayer->where('teams_id', '=', $idTeam)->where('status', '=', '1')
        ->where('role_team', '=', 'Master')
        ->first();
        if (!$dataMaster) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Master not found!'
            ], 404);
        }
        $dataGameAccountMaster = $this->gameAccount->where('id_game_account', $dataMaster->game_accounts_id)->first();
        $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.status','=','1')->get(['game_accounts.id_game_account', 'game_accounts.nickname','team_players.role_team']);
        foreach ($dataPlayers as $value) {
            $resultDataPlayers[] = $value;
        }
        try {
            $this->teamPlayer = new TeamPlayer();
            $this->teamPlayer->id = Uuid::uuid4()->toString();
            $this->teamPlayer->teams_id = $idTeam;
            $this->teamPlayer->game_accounts_id = $sessGameAccount->id_game_account;
            $this->teamPlayer->role_team = 'Member';
            $this->teamPlayer->status = '0';
            if ($this->teamPlayer->save()) {
                $details = [
                    'id' => $idTeam,
                    'games_id' => $sessGame['game']['id'],
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => [
                        'id' => $dataMaster->id,
                        'game_accounts_id' => $dataMaster->game_accounts_id,
                        'nickname' => $dataGameAccountMaster->nickname,
                        'role_team' => $dataMaster->role_team,
                        'status' => $dataMaster->status,
                    ],
                    'created_at' => $dataTeam->created_at,
                    'message' => $sessGameAccount->nickname.' want to join '.$dataTeam->name.' team!',
                    'game' => [
                        'id' => $sessGame['game']['id'],
                        'name' => $sessGame['game']['name'],
                        'logo' => $sessGame['game']['picture'],
                    ],
                    'member-team' => $resultDataPlayers,
                ];
                $user = $this->user->where('id', $dataGameAccountMaster->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'You want to join the team!',
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function leaveTeam(Request $request, $idTeam)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGameAccount = $request->session()->get('game_account');
        // $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        try {
            $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('teams_id', '=', $idTeam)
            ->delete();
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => "You've left the team!",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
