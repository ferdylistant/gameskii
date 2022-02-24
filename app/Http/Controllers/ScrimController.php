<?php

namespace App\Http\Controllers;

use App\Models\Scrim;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScrimController extends Controller
{
    public function __construct()
    {
        $this->scrim = new Scrim();
        $this->gameAccount = new GameAccount();
        
    }
    public function getAllScrims(Request $request)
    {
        $dataScrim = $this->scrim->join('games','scrims.games_id','=','games.id')
        ->join('top_banner_games','games.id','=','top_banner_games.games_id')
        ->join('bottom_banner_games','games.id','=','bottom_banner_games.games_id')
        ->join('game_accounts','scrims.game_accounts_id','=','game_accounts.id')
        ->join('ranks', 'scrims.ranks_id', '=', 'ranks.id')
        ->join('users','game_accounts.users_id','=','users.id')
        ->select('scrims.*','games.name as game_name', 'games.picture','game_accounts.nickname','top_banner_games.path as top_banner_url','bottom_banner_games.path as bottom_banner_url','users.name as user_name',
        'users.avatar as user_avatar','ranks.class','ranks.logo')
        ->get();
        try {
            $arrayData = [
                'status' => 'success',
                'message' => 'Data Scrim',
                'data' => [
                    'scrims' => [
                        'id' => $dataScrim[0]->id,
                        'name_scrim' => $dataScrim[0]->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$dataScrim[0]->image),
                        'quota' => $dataScrim[0]->quota,
                        'scrim_system' => $dataScrim[0]->scrim_system,
                        'scrim_date' => $dataScrim[0]->scrim_date,
                        'status' => $dataScrim[0]->status,
                        'result' => $dataScrim[0]->result,
                        'created_at' => $dataScrim[0]->created_at,
                        'updated_at' => $dataScrim[0]->updated_at,
                    ],
                    'scrim-master' => [
                        'id_game_account' => $dataScrim[0]->game_accounts_id,
                        'nickname' => $dataScrim[0]->nickname,
                        'name' => $dataScrim[0]->user_name,
                        'picture' => URL::to('/api/avatar/'.$dataScrim[0]->avatar),
                    ],
                    'scrim-game' => [
                        'id_game' => $dataScrim[0]->games_id,
                        'name' => $dataScrim[0]->game_name,
                        'picture' => URL::to('/api/picture-game/'.$dataScrim[0]->picture),
                        'top_banner' => URL::to('/api/banner-game/top/'.$dataScrim[0]->top_banner_url),
                        'bottom_banner' => URL::to('/api/banner-game/bottom'.$dataScrim[0]->bottom_banner_url),
                    ],
                    'scrim-rank' => [
                        'id_rank' => $dataScrim[0]->ranks_id,
                        'class' => $dataScrim[0]->class,
                        'logo' => URL::to('/api/logo-rank/'.$dataScrim[0]->logo),
                    ],
                ]
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
        $user = auth('user')->user();
        $gameAccount = $this->gameAccount->where('id_game_account', '=', $sessGameAccount->id_game_account)
        ->where('users_id', '=', $user->id)->where('games_id', '=', $sessGame['game']['id'])->first();
        if (!$gameAccount) {
            return response()->json([
                'status' => 'error',
                'message' => "You don't have a game account"
            ], 404);
        }
        $scrims = $this->scrim->where('game_accounts_id','=', $gameAccount->id_game_account)->get();
        if (!$scrims) {
            return response()->json([
                'status' => 'error',
                'message' => "You don't have any scrims"
            ], 404);
        }
        try {
            foreach ($scrims as $scrim) {
                $loopScrims[] = $scrim;
            }
            $arrayData = [
                'status' => 'success',
                'message' => 'Data Scrim',
                'data' => $loopScrims
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function getMyScrimId(Request $request, $idScrim){
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource'
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        try {
            $scrim = $this->scrim->where('id', '=', $idScrim)
            ->where('games_id','=',$sessGame['game']['id'])->first();
            if (!$scrim) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Scrim not found'
                ], 404);
            }
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
            ], 401);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
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
                $this->games_id = $sessGame['game']['id'];
                $this->ranks_id = $request->ranks_id;
                $this->game_accounts_id = $sessGameAccount->id;
                $this->name_party = $request->name_party;
                $this->image = $imageName;
                $this->quota = $request->quota;
                $this->scrim_system = $request->scrim_system;
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
                        'code' => 201,
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
                        'code' => 201,
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
