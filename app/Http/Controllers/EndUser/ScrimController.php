<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\Scrim;
use App\Models\Rank;
use Ramsey\Uuid\Uuid;
use App\Models\ScrimMatch;
use Illuminate\Http\Request;
use App\Models\GameAccount;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ScrimController extends Controller
{
    public function __construct()
    {
        $this->scrim = new Scrim();
        $this->scrimMatch = new ScrimMatch();
        $this->gameAccount = new GameAccount();
        $this->rank = new Rank();

    }
    public function getAllScrims(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this route'
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        if ($sessGame == null) {
            $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
            $game_account->is_online = 0;
            $game_account->save();
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        $dataScrim = $this->scrim->join('games','games.id','=','scrims.games_id')
        ->join('top_banner_games','top_banner_games.games_id','=','games.id')
        ->join('bottom_banner_games','bottom_banner_games.games_id','=','games.id')
        ->join('game_accounts','game_accounts.id_game_account','=','scrims.game_accounts_id')
        ->join('users','game_accounts.users_id','=','users.id')
        ->select('scrims.*','games.name as game_name', 'games.picture','game_accounts.nickname','top_banner_games.path as top_banner_url','bottom_banner_games.path as bottom_banner_url','users.name as user_name',
        'users.avatar')
        ->where('scrims.games_id','=',$sessGame['game']['id'])
        ->get();
        if ($dataScrim->count() < '1') {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }
        try {
            foreach ($dataScrim as $value) {
                $data[] = [
                    'scrims' => [
                        'id' => $value->id,
                        'name_scrim' => $value->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'team_play' => $this->scrimMatch->where('scrims_id',$value->id)->count(),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'result' => $value->result,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
                    ],
                    'scrim-master' => [
                        'id_game_account' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'name' => $value->user_name,
                        'picture' => $value->avatar,
                    ],
                    'scrim-game' => [
                        'id_game' => $value->games_id,
                        'name' => $value->game_name,
                        'picture' => URL::to('/api/picture-game/'.$value->picture),
                        'top_banner' => URL::to('/api/banner-game/top/'.$value->top_banner_url),
                        'bottom_banner' => URL::to('/api/banner-game/bottom'.$value->bottom_banner_url),
                    ],
                ];
            }
            $arrayData = [
                'status' => 'success',
                'message' => 'Data Scrim',
                'data' => $data
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getMyScrims(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'It is not your role'
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
        $user = auth('user')->user();
        $gameAccount = $this->gameAccount->where('id_game_account', '=', $sessGameAccount->id_game_account)
        ->where('users_id', '=', $user->id)->where('games_id', '=', $sessGame['game']['id'])->first();
        if (!$gameAccount) {
            return response()->json([
                'status' => 'error',
                'message' => "You don't have a game account"
            ], 404);
        }
        $scrims = $this->scrim->where('game_accounts_id','=', $gameAccount->id_game_account)
        ->where('games_id','=', $sessGame['game']['id'])
        ->get();
        if ($scrims->count() == 0) {
            return response()->json([
                'status' => 'error',
                'message' => "You don't have any scrims"
            ], 404);
        }

        try {
            foreach ($scrims as $scrim) {
                $data[] = [
                    'id' => $scrim->id,
                    'games_id' => $scrim->games_id,
                    'rank' => $this->rank->where('id',$scrim->ranks_id)
                    ->select('id','class')
                    ->first(),
                    'name_party' => $scrim->name_party,
                    'image' => URL::to('/api/picture-scrim/'.$scrim->image),
                    'team_play' => $this->scrimMatch->where('scrims_id','=', $scrim->id)->get()->count(),
                    'quota' => $scrim->quota,
                    'scrim_system' => $scrim->scrim_system,
                    'scrim_date' => $scrim->scrim_date,
                    'status' => $scrim->status,
                    'result' => $scrim->result,
                    'created_at' => $scrim->created_at,
                    'updated_at' => $scrim->updated_at,
                ];
            }
            $arrayData = [
                'status' => 'success',
                'message' => 'My Data Scrim',
                'data' => $data
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getMyScrimId(Request $request, $idScrim)
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
            $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
            $game_account->is_online = 0;
            $game_account->save();
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $scrim = $this->scrim->where('id', '=', $idScrim)
            ->where('games_id','=',$sessGame['game']['id'])
            ->where('game_accounts_id','=',$sessGameAccount->id_game_account)
            ->first();
            if (!$scrim) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
            $arrayData = [
                'status' => 'success',
                'message' => "Data Scrim "."'".$scrim->name_party."'",
                'data' => [
                    'id' => $scrim->id,
                    'games_id' => $scrim->games_id,
                    'rank' => $this->rank->where('id',$scrim->ranks_id)
                    ->select('id','class')
                    ->first(),
                    'name_party' => $scrim->name_party,
                    'image' => URL::to('/api/picture-scrim/'.$scrim->image),
                    'team_play' => $this->scrimMatch->where('scrims_id','=', $scrim->id)->get()->count(),
                    'quota' => $scrim->quota,
                    'scrim_system' => $scrim->scrim_system,
                    'scrim_date' => $scrim->scrim_date,
                    'status' => $scrim->status,
                    'result' => $scrim->result,
                    'created_at' => $scrim->created_at,
                    'updated_at' => $scrim->updated_at,
                ]
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function createScrim(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create scrim'
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
        $data = $this->scrim->where('games_id', '=', $sessGame['game']['id'])
        ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
        ->first();

        if ($data) {
            $dateCreated = new Carbon($data->created_at, 'Asia/Jakarta');
            $diffDays = $dateCreated->isToday();
            if ($diffDays) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can create scrim only once a day',
                ], 403);
            }
        }
        // return response()->json($sessGameAccount);
        //Mobile Legends
        if ($sessGame['game']['id'] == '3d9fe0a9-a052-48c9-95c6-1ca494fe93c3')
        {
            $validator = Validator::make($request->all(), [
                'name_party' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
                'quota' => 'required|integer',
                'scrim_system' => 'required|string|max:255',
                'scrim_date' => 'required|date',
                'ranks_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            try {
                $dataFile = $request->file('image');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $this->scrim->id = Uuid::uuid4()->toString();
                $this->scrim->games_id = $sessGame['game']['id'];
                $this->scrim->ranks_id = $request->ranks_id;
                $this->scrim->game_accounts_id = $sessGameAccount->id_game_account;
                $this->scrim->name_party = $request->name_party;
                $this->scrim->image = $imageName;
                $this->scrim->quota = $request->quota;
                $this->scrim->scrim_system = $request->scrim_system;
                $this->scrim->scrim_date = $request->scrim_date;
                if ($this->scrim->save()) {
                    $dataFile->move(storage_path('uploads/picture-scrim'), $imageName);
                    $dataScrim = Scrim::join('ranks', 'ranks.id', '=', 'scrims.ranks_id')
                        ->join('game_accounts', 'game_accounts.id_game_account', '=', 'scrims.game_accounts_id')
                        ->join('games', 'games.id', '=', 'scrims.games_id')
                        ->select('scrims.*', 'ranks.class', 'game_accounts.nickname', 'games.name as game')
                        ->where('scrims.id', $this->scrim->id)
                        ->first();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Scrim created successfully',
                        'data' => [
                            'scrim' => [
                                'id' => $dataScrim->id,
                                'name_party' => $dataScrim->name_party,
                                'image' => URL::to('/api/picture-scrim/'.$dataScrim->image),
                                'team_play' => $this->scrimMatch->where('scrims_id','=', $dataScrim->id)->get()->count(),
                                'quota' => $dataScrim->quota,
                                'scrim_system' => $dataScrim->scrim_system,
                                'scrim_date' => $dataScrim->scrim_date,
                                'status' => $dataScrim->status,
                                'result' => $dataScrim->result,
                                'created_at' => $dataScrim->created_at,
                                'updated_at' => $dataScrim->updated_at,
                            ],
                            'scrim-master' => [
                                'id_account_game' => $dataScrim->game_accounts_id,
                                'nickname' => $dataScrim->nickname,
                            ],
                            'rank-requirement' => [
                                'id_rank' => $dataScrim->ranks_id,
                                'class' => $dataScrim->class,
                            ],
                            'game' => [
                                'id_game' => $dataScrim->games_id,
                                'name' => $dataScrim->game,
                            ],
                        ]
                    ], 201);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }
        if (($sessGame['game']['id'] == '42e7db33-330b-4b4b-87c8-d255f7dca901') || ($sessGame['game']['id'] == '7c26d6b9-72a7-4b56-90de-486e71923f48'))
        {
            $validator = Validator::make($request->all(), [
                'name_party' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
                'quota' => 'required|integer',
                'scrim_date' => 'required|date',
                'ranks_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            try {
                $dataFile = $request->file('image');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $this->scrim->id = Uuid::uuid4()->toString();
                $this->scrim->games_id = $sessGame['game']['id'];
                $this->scrim->ranks_id = $request->ranks_id;
                $this->scrim->game_accounts_id = $sessGameAccount->id;
                $this->scrim->name_party = $request->name_party;
                $this->scrim->image = $imageName;
                $this->scrim->quota = $request->quota;
                // $this->scrim_system = $request->scrim_system;
                $this->scrim_date = $request->scrim_date;
                if ($this->save()) {
                    $dataFile->move(storage_path('uploads/picture-scrim'), $imageName);
                    $dataScrim = $this->scrim->join('ranks', 'ranks.id', '=', 'scrims.ranks_id')
                        ->join('game_accounts', 'game_accounts.id_game_account', '=', 'scrims.game_accounts_id')
                        ->join('games', 'games.id', '=', 'scrims.games_id')
                        ->select('scrims.*', 'ranks.name as rank', 'game_accounts.name as game_account', 'games.name as game')
                        ->where('scrims.id', $this->scrim->id)
                        ->first();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Scrim created successfully',
                        'data' => $dataScrim
                    ], 201);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }
    }
}
