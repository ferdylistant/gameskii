<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Team;
use App\Models\Game;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\TeamPlayer;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
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
        $this->game = new Game();
    }
    public function getListInvitationFromMaster(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $teamPlayer = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
                ->join('game_accounts', 'team_players.game_accounts_id', '=', 'game_accounts.id_game_account')
                ->join('users', 'game_accounts.users_id', '=', 'users.id')
                ->where('team_players.status', '0')
                ->where('team_players.role_team','Member')
                ->where('team_players.game_accounts_id', $sessGameAccount->id_game_account)
                ->where('teams.games_id', $sessGame['game']['id'])
                ->select('team_players.role_team', 'teams.*', 'game_accounts.nickname', 'users.phone', 'users.avatar', 'users.email')
                ->first();
                if ($teamPlayer == null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No data found'
                    ], 404);
                }
            $teamPlayerMaster = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
                ->join('game_accounts', 'team_players.game_accounts_id', '=', 'game_accounts.id_game_account')
                ->join('users', 'game_accounts.users_id', '=', 'users.id')
                ->where('team_players.status', '1')
                ->where('team_players.role_team','Master')
                ->where('teams.games_id', $sessGame['game']['id'])
                ->select('team_players.role_team','team_players.game_accounts_id', 'teams.*', 'game_accounts.nickname', 'users.phone', 'users.avatar', 'users.email')
                ->get();
            if ($teamPlayerMaster->count() < '1') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data Master found'
                ], 404);
            }
            foreach ($teamPlayerMaster as $value) {
                $result[] = [
                    'team' => [
                        'id' => $value->id,
                        'games_id' => $value->games_id,
                        'ranks_id' => $value->ranks_id,
                        'name' => $value->name,
                        'logo' => URL::to('/api/picture-team/' . $value->logo),
                        'won' => $value->won,
                        'lose' => $value->lose,
                        'total_match_scrim' => $value->total_match_scrim,
                        'total_match_tournament' => $value->total_match_tournament,
                        'point' => $value->point,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
                    ],
                    'master' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'role_team' => $value->role_team,
                        'avatar' => $value->avatar,
                    ],
                ];
            }
            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getListMemberJoins(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $teamPlayer = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
                ->join('game_accounts', 'team_players.game_accounts_id', '=', 'game_accounts.id_game_account')
                ->join('users', 'game_accounts.users_id', '=', 'users.id')
                ->where('team_players.status', '0')
                ->where('team_players.role_team','Member')
                ->where('teams.games_id', $sessGame['game']['id'])
                ->select('teams.*', 'team_players.game_accounts_id','game_accounts.nickname', 'users.phone', 'users.avatar', 'users.email')
                ->get();
            if ($teamPlayer->count() < '1') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data found'
                ], 404);
            }
            foreach ($teamPlayer as $value) {
                $result[] = [
                    'team' => [
                        'id' => $value->id,
                        'games_id' => $value->games_id,
                        'ranks_id' => $value->ranks_id,
                        'name' => $value->name,
                        'logo' => URL::to('/api/picture-team/' . $value->logo),
                        'won' => $value->won,
                        'lose' => $value->lose,
                        'total_match_scrim' => $value->total_match_scrim,
                        'total_match_tournament' => $value->total_match_tournament,
                        'point' => $value->point,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
                    ],
                    'list-join' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'role_team' => $value->role_team,
                        'avatar' => $value->avatar,
                    ],
                ];
            }
            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getAllTeams(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => "You don't have permission to access this resource"
            ],403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout, please login again'
            ],408);
        }
        try{
            $dataTeam = $this->team->join('games','games.id','=','teams.games_id')
            ->where('teams.games_id',$sessGame['game']['id'])
            ->select('teams.*','games.name as name_game','games.picture')
            ->get();
            // return response()->json($dataTeam);
            if ($dataTeam->count() == '0') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found'
                ],404);
            }
            foreach ($dataTeam as $value) {
                $resultTeam[] = [
                        'team' => [
                            'id' => $value->id,
                            'name' => $value->name,
                            'logo' => URL::to('/api/picture-team/'.$value->logo),
                            'won' => $value->won,
                            'lose' => $value->lose,
                            'total_match_scrim' => $value->total_match_scrim,
                            'total_match_tournament' => $value->total_match_tournament,
                            'point' => $value->point,
                            'created_at' => $value->created_at,
                            'updated_at' => $value->updated_at,
                        ],
                        'member-team' => $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                        ->join('users', 'users.id', '=', 'game_accounts.users_id')
                        ->where('team_players.status', '1')
                        ->where('team_players.teams_id', $value->id)
                        ->select('game_accounts.id_game_account','game_accounts.nickname','users.email','users.phone','users.avatar','team_players.role_team')
                        ->get(),
                        'game' => [
                            'id_game' => $value->games_id,
                            'name_game' => $value->name_game,
                            'picture' => URL::to('/api/picture-game/'.$value->picture),
                        ],
                    ];
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get all teams success',
                'data' => $resultTeam
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getMyTeams(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3')
        {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        // return response()->json($userGame);
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try{
            $dataMyTeam = $this->team->join('team_players', 'team_players.teams_id', '=', 'teams.id')
            ->where('team_players.game_accounts_id', $sessGameAccount->id_game_account)
            ->where('teams.games_id', $sessGame['game']['id'])
            ->where('team_players.status', '1')
            ->select('teams.*', 'team_players.role_team')
            ->get();
            if ($dataMyTeam->count() == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You dont have any team'
                ], 404);
            }
            foreach ($dataMyTeam as $item) {
                $dataTeam[] = [
                    'id' => $item->id,
                    'games_id' => $item->games_id,
                    'name' => $item->name,
                    'logo' => URL::to('/api/picture-team/'.$item->logo),
                    'won' => $item->won,
                    'lose' => $item->lose,
                    'total_match_scrim' => $item->total_match_scrim,
                    'total_match_tournament' => $item->total_match_tournament,
                    'point' => $item->point,
                    'created_at' => $item->created_at,
                    'master-team' => $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                    ->join('teams', 'team_players.teams_id', '=', 'teams.id')
                    ->where('team_players.teams_id', $item->id)
                    ->where('team_players.role_team', 'Master')
                    ->select('game_accounts.id_game_account', 'game_accounts.nickname')
                    ->first(),
                    'member-team' => $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                    ->join('teams', 'team_players.teams_id', '=', 'teams.id')
                    ->where('team_players.teams_id', $item->id)
                    ->get(),
                    'role_team' => $item->role_team,
                ];
            }
            return response()->json([
                'status' => 'success',
                'data' => $dataTeam
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTeam(Request $request, $idTeam)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3')
        {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $dataTeam = $this->team->where('teams.id', $idTeam)
        ->where('teams.games_id', $sessGame['game']['id'])
        ->first();
        if (!$dataTeam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found'
            ], 404);
        }
        try {
            $dataTeamPlayer = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('teams', 'team_players.teams_id', '=', 'teams.id')
            ->where('team_players.teams_id', $idTeam)
            ->where('team_players.status', '1')
            ->where('teams.games_id', $sessGame['game']['id'])
            ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'team_players.role_team')
            ->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Get team success',
                'data' => [
                    'team' => [
                        'id' => $dataTeam->id,
                        'games_id' => $dataTeam->games_id,
                        'name' => $dataTeam->name,
                        'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                        'won' => $dataTeam->won,
                        'lose' => $dataTeam->lose,
                        'total_match_scrim' => $dataTeam->total_match_scrim,
                        'total_match_tournament' => $dataTeam->total_match_tournament,
                        'point' => $dataTeam->point,
                        'created_at' => $dataTeam->created_at,
                        'master-team' => $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                        ->join('teams', 'team_players.teams_id', '=', 'teams.id')
                        ->where('team_players.teams_id', $dataTeam->id)
                        ->where('team_players.role_team', 'Master')
                        ->where('teams.games_id', $sessGame['game']['id'])
                        ->select('game_accounts.id_game_account', 'game_accounts.nickname')
                        ->first(),
                    ],
                    'member-team' => $dataTeamPlayer,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function createTeam(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3')
        {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
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
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $alreadyHaveTeam = $this->team->join('team_players', 'team_players.teams_id', '=', 'teams.id')
            ->where('teams.games_id', $sessGame['game']['id'])
            ->where('team_players.game_accounts_id', $sessGameAccount->id_game_account)
            ->where('team_players.status', '1')
            ->first();
            // return response()->json($alreadyHaveTeam);
            if ($alreadyHaveTeam) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have a team'
                ], 403);
            }
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
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $masterTeam = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.game_accounts_id', $idGameAccount)
        ->where('team_players.status', '1')
        ->where('team_players.role_team', 'Master')
        ->where('teams.games_id', $sessGame['game']['id'])
        ->select('team_players.game_accounts_id')
        ->first();
        if ($masterTeam->game_accounts_id == $sessGameAccount->id_game_account){
            return response()->json([
                'status' => 'error',
                'message' => 'You are master of this team, cannot add yourself to your team'
            ], 403);
        }
        $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        if (!$gameAccount) {
            return response()->json([
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
                'status' => 'error',
                'message' => "You're not be friend with this account!"
            ], 404);
        }
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataGame = $this->game->where('id', $dataTeam->games_id)->first();
        $dataMaster = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
        ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->join('users', 'users.id', '=', 'game_accounts.users_id')
        ->where('team_players.game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.role_team', '=', 'Master')
        ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email','users.avatar')
        ->first();
        if (!$dataMaster) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not master of this team!'
            ], 404);
        }
        $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->join('users', 'users.id', '=', 'game_accounts.users_id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.status','=','1')
        ->select('game_accounts.id_game_account', 'game_accounts.nickname','users.phone','users.email','team_players.role_team','users.avatar')
        ->get();
        try {
            $this->teamPlayer = new TeamPlayer();
            $this->teamPlayer->id = Uuid::uuid4()->toString();
            $this->teamPlayer->teams_id = $idTeam;
            $this->teamPlayer->game_accounts_id = $idGameAccount;
            $this->teamPlayer->role_team = 'Member';
            $this->teamPlayer->status = '0';
            if ($this->teamPlayer->save()){
                $user = $this->user->where('id', $gameAccount->users_id)->first();
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataTeam->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMaster,
                    'created_at' => $dataTeam->created_at,
                    'message' => $dataMaster->nickname.' invited you to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Member added successfully!',
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptJoinTeam(Request $request,$idTeam, $idGameAccount) //for Master
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $dataTeam = $this->team->where('id', '=', $idTeam)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataTeam) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found!'
                ], 404);
            }
            $dataMasterAccess = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
            ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('users', 'users.id', '=', 'game_accounts.users_id')
            ->where('team_players.game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('team_players.teams_id', '=', $idTeam)
            ->where('team_players.role_team', '=', 'Master')
            ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email','users.avatar')
            ->first();
            if (!$dataMasterAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not master of this team!'
                ], 403);
            }
            $alreadyAccept = $this->teamPlayer->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $idGameAccount)
            ->where('status', '=', '1')
            ->first();
            if ($alreadyAccept) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Member already accepted!'
                ], 404);
            }
            $dataTeamPlayer = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $idGameAccount)
            ->where('status', '=', '0')
            ->first();
            if (!$dataTeamPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No member to accept!'
                ], 404);
            }
            $dataTeamPlayer->status = '1';
            if ($dataTeamPlayer->save()) {
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')
                ->select('game_accounts.id_game_account', 'game_accounts.nickname','users.phone','users.email','team_players.role_team','users.avatar')
                ->get();
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataTeam->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMasterAccess,
                    'created_at' => $dataTeam->created_at,
                    'message' => 'Master accepted your invitation to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user = $this->user->where('id', $dataTeamPlayer->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Member accepted successfully!'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptInvitationMember(Request $request, $idTeam) //for Member
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $dataTeam = $this->team->where('id', '=', $idTeam)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataTeam) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found!'
                ], 404);
            }
            $dataMasterAccess = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
            ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('users', 'users.id', '=', 'game_accounts.users_id')
            ->where('team_players.teams_id', '=', $idTeam)
            ->where('team_players.role_team', '=', 'Master')
            ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email','users.avatar')
            ->first();
            if (!$dataMasterAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Master not found!'
                ], 403);
            }
            $alreadyAccept = $this->teamPlayer->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status', '=', '1')
            ->first();
            if ($alreadyAccept) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already joined this team!'
                ], 404);
            }
            $dataTeamPlayer = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status', '=', '0')
            ->first();
            if (!$dataTeamPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No request to accept!'
                ], 404);
            }
            $dataTeamPlayer->status = '1';
            if ($dataTeamPlayer->save()) {
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')
                ->select('game_accounts.id_game_account', 'game_accounts.nickname','users.phone','users.email','team_players.role_team','users.avatar')
                ->get();
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataTeam->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMasterAccess,
                    'created_at' => $dataTeam->created_at,
                    'message' => $dataTeamPlayer->nickname.' have accepted your invitation to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user = $this->user->where('id', $dataTeamPlayer->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Request accepted successfully!'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectJoinTeam(Request $request, $idTeam,$idGameAccount) //for Master
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $dataTeam = $this->team->where('id', '=', $idTeam)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataTeam) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found!'
                ], 404);
            }
            $dataMasterAccess = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
            ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('users', 'users.id', '=', 'game_accounts.users_id')
            ->where('team_players.game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('team_players.teams_id', '=', $idTeam)
            ->where('team_players.role_team', '=', 'Master')
            ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email', 'users.avatar')
            ->first();
            if (!$dataMasterAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not master of this team!'
                ], 403);
            }
            $alreadyReject = $this->teamPlayer->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status', '=', '2')
            ->first();
            if ($alreadyReject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already rejected this request!'
                ], 403);
            }
            $dataTeamPlayer = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $idGameAccount)
            ->where('status', '=', '0')
            ->first();
            if (!$dataTeamPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No request to reject!'
                ], 404);
            }
            $dataTeamPlayer->status = '2';
            if ($dataTeamPlayer->save()) {
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')
                ->select('game_accounts.id_game_account', 'game_accounts.nickname','users.phone','users.email','team_players.role_team','users.avatar')
                ->get();
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataTeam->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMasterAccess,
                    'created_at' => $dataTeam->created_at,
                    'message' => 'Master have rejected your invitation to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user = $this->user->where('id', $dataTeamPlayer->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Request rejected successfully!'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectInvitationMember(Request $request, $idTeam) //for Member
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if (($sessGame == null) || ($sessGameAccount == null)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout'
                ], 408);
            }
            $dataTeam = $this->team->where('id', '=', $idTeam)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataTeam) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team not found!'
                ], 404);
            }
            $dataMasterAccess = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
            ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->join('users', 'users.id', '=', 'game_accounts.users_id')
            ->where('team_players.teams_id', '=', $idTeam)
            ->where('team_players.role_team', '=', 'Master')
            ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email','users.avatar')
            ->first();
            if (!$dataMasterAccess) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Master not found!'
                ], 403);
            }
            $alreadyReject = $this->teamPlayer->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status', '=', '2')
            ->first();
            if ($alreadyReject) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already rejected this request!'
                ], 403);
            }
            $dataTeamPlayer = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
            ->where('teams_id', '=', $idTeam)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status', '=', '0')
            ->first();
            if (!$dataTeamPlayer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No request to reject!'
                ], 404);
            }
            $dataTeamPlayer->status = '2';
            if ($dataTeamPlayer->save()) {
                $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->where('team_players.teams_id', '=', $idTeam)
                ->where('team_players.status','=','1')
                ->select('game_accounts.id_game_account', 'game_accounts.nickname','users.phone','users.email','team_players.role_team','users.avatar')
                ->get();
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataTeam->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMasterAccess,
                    'created_at' => $dataTeam->created_at,
                    'message' => $dataTeamPlayer->nickname.' have rejected your invitation to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user = $this->user->where('id', $dataTeamPlayer->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Rejected successfully!'
                ], 200);
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
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataGame = $this->game->where('id', $dataTeam->games_id)->first();
        $dataMaster = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
        ->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->join('users', 'users.id', '=', 'game_accounts.users_id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.role_team', '=', 'Master')
        ->select('game_accounts.id_game_account', 'game_accounts.nickname', 'users.phone', 'users.email','users.avatar')
        ->first();
        if (!$dataMaster) {
            return response()->json([
                'status' => 'error',
                'message' => 'Master not found!'
            ], 404);
        }
        $dataTeamOwn = $this->teamPlayer->join('teams', 'team_players.teams_id', '=', 'teams.id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('team_players.status', '=', '1')
        ->where('team_players.role_team', '=', 'Master')
        ->select('teams.id')
        ->first();
        if ($dataTeamOwn) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot join to your own team!'
            ], 403);
        }
        $alreadyJoin = $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('teams_id', '=', $idTeam)
        ->where('status', '=', '0')
        ->first();
        if ($alreadyJoin) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already try to join this team, wait for the master to accept your join request!'
            ], 409);
        }
        $alreadyTeam = $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->where('teams_id', '=', $idTeam)
        ->where('status', '=', '1')
        ->first();
        if ($alreadyTeam) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already joined this team!'
            ], 409);
        }
        $dataPlayers = $this->teamPlayer->join('game_accounts', 'game_accounts.id_game_account', '=', 'team_players.game_accounts_id')
        ->join('users', 'users.id', '=', 'game_accounts.users_id')
        ->where('team_players.teams_id', '=', $idTeam)
        ->where('team_players.status','=','1')
        ->select('game_accounts.id_game_account', 'game_accounts.nickname','team_players.role_team', 'users.phone','users.email','users.avatar')
        ->get();
        try {
            $this->teamPlayer = new TeamPlayer();
            $this->teamPlayer->id = Uuid::uuid4()->toString();
            $this->teamPlayer->teams_id = $idTeam;
            $this->teamPlayer->game_accounts_id = $sessGameAccount->id_game_account;
            $this->teamPlayer->role_team = 'Member';
            $this->teamPlayer->status = '0';
            if ($this->teamPlayer->save()) {
                $details = [
                    'id' => $dataTeam->id,
                    'games_id' => $dataGame->games_id,
                    'name' => $dataTeam->name,
                    'logo' => URL::to('/api/picture-team/'.$dataTeam->logo),
                    'ranks_id' => $dataTeam->ranks_id,
                    'won' => $dataTeam->won,
                    'lose' => $dataTeam->lose,
                    'total_match_scrim' => $dataTeam->total_match_scrim,
                    'total_match_tournament' => $dataTeam->total_match_tournament,
                    'point' => $dataTeam->point,
                    'master' => $dataMaster,
                    'created_at' => $dataTeam->created_at,
                    'message' => $sessGameAccount->nickname.' want to join '.$dataTeam->name.' team!',
                    'member-team' => $dataPlayers,
                ];
                $user = $this->user->where('id', $dataMaster->users_id)->first();
                $user->notify(new TeamNotification($details));
                return response()->json([
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
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if ($sessGame == null || $sessGameAccount == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $dataMaster = $this->teamPlayer->where('teams_id', '=', $idTeam)->where('status', '=', '1')->where('role_team', '=', 'Master')->first();
        if ($dataMaster) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can not leave this team, because you are the master of this team!'
            ], 409);
        }
        // $gameAccount = $this->gameAccount->where('id_game_account',$idGameAccount)->first();
        $dataTeam = $this->team->where('id', $idTeam)->first();
        if (!$dataTeam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found!'
            ], 404);
        }
        $dataTeamPlayer = $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)->where('teams_id', '=', $idTeam)
        ->where('status', '=', '1')
        ->first();
        if (!$dataTeamPlayer) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not a member of this team!'
            ], 409);
        }
        try {
            $this->teamPlayer->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('teams_id', '=', $idTeam)
            ->delete();
            return response()->json([
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
