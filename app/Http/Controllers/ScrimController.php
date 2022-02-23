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
    public function getMyScrims(Request $request)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                'status' => 'error',
                'message' => 'It is not your role'
            ], 403);
        }
        $user = auth('user')->user();
        $scrims = $this->scrim->where('user_id', $user->id)->get();
        try {
            $arrayData = [
                'status' => 'success',
                'message' => 'Data Scrim',
                'data' => $scrims
            ];
            return response()->json($arrayData, 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function getMyScrim(Request $request, $idScrim){
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
