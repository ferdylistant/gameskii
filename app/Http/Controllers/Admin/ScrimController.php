<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Scrim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

class ScrimController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->scrim = new Scrim();
    }
    public function getScrims()
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $scrim = $this->scrim->join('game_accounts', 'scrims.game_accounts_id', '=', 'game_accounts.id_game_account')
            ->join('users', 'game_accounts.users_id', '=', 'users.id')
            ->join('games', 'scrims.games_id', '=', 'games.id')
            ->select('scrims.*', 'game_accounts.nickname', 'users.name', 'users.phone', 'users.email','users.avatar', 'games.name as game_name', 'games.picture')
            ->get();
            if ($scrim->count() < '1') {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no scrim"
                ], 404);
            }
            foreach ($scrim as $value) {
                $result[] = [
                    'scrim' => [
                        'id' => $value->id,
                        'ranks_id' => $value->ranks_id,
                        'name_party' => $value->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'result' => $value->result,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'master-scrim' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'name' => $value->name,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'avatar' => $value->avatar
                    ],
                    'game-scrim' => [
                        'id' => $value->games_id,
                        'name' => $value->game_name,
                        'picture' => URL::to('/api/picture-game/'.$value->picture),
                    ]
                ];
            }
            return response()->json([
                "status" => "success",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getScrimById($idScrim)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $scrim = $this->scrim->join('game_accounts', 'scrims.game_accounts_id', '=', 'game_accounts.id_game_account')
            ->join('users', 'game_accounts.users_id', '=', 'users.id')
            ->join('games', 'scrims.games_id', '=', 'games.id')
            ->select('scrims.*', 'game_accounts.nickname', 'users.name', 'users.phone', 'users.email','users.avatar', 'games.name as game_name', 'games.picture')
            ->where('scrims.id', '=', $idScrim)
            ->first();
            if ($scrim == null) {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no scrim"
                ], 404);
            }
            $result = [
                'scrim' => [
                    'id' => $scrim->id,
                    'ranks_id' => $scrim->ranks_id,
                    'name_party' => $scrim->name_party,
                    'image' => URL::to('/api/picture-scrim/'.$scrim->image),
                    'quota' => $scrim->quota,
                    'scrim_system' => $scrim->scrim_system,
                    'scrim_date' => $scrim->scrim_date,
                    'status' => $scrim->status,
                    'result' => $scrim->result,
                    'created_at' => $scrim->created_at,
                    'updated_at' => $scrim->updated_at
                ],
                'master-scrim' => [
                    'game_accounts_id' => $scrim->game_accounts_id,
                    'nickname' => $scrim->nickname,
                    'name' => $scrim->name,
                    'phone' => $scrim->phone,
                    'email' => $scrim->email,
                    'avatar' => $scrim->avatar
                ],
                'game-scrim' => [
                    'id' => $scrim->games_id,
                    'name' => $scrim->game_name,
                    'picture' => URL::to('/api/picture-game/'.$scrim->picture),
                ]
            ];
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getScrimByIdUser($idUser)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $scrim = $this->scrim->join('game_accounts', 'scrims.game_accounts_id', '=', 'game_accounts.id_game_account')
            ->join('users', 'game_accounts.users_id', '=', 'users.id')
            ->join('games', 'scrims.games_id', '=', 'games.id')
            ->select('scrims.*', 'game_accounts.nickname', 'users.name', 'users.phone', 'users.email','users.avatar', 'games.name as game_name', 'games.picture')
            ->where('users.id', '=', $idUser)
            ->get();
            if ($scrim->count() < '1') {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no scrim"
                ], 404);
            }
            foreach ($scrim as $value) {
                $result[] = [
                    'scrim' => [
                        'id' => $value->id,
                        'ranks_id' => $value->ranks_id,
                        'name_party' => $value->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'result' => $value->result,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'master-scrim' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'name' => $value->name,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'avatar' => $value->avatar
                    ],
                    'game-scrim' => [
                        'id' => $value->games_id,
                        'name' => $value->game_name,
                        'picture' => URL::to('/api/picture-game/'.$value->picture),
                    ]
                ];
            }
            return response()->json([
                "status" => "success",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getScrimByIdGameAccount($idGameAccount)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3" ) {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $scrim = $this->scrim->join('game_accounts', 'scrims.game_accounts_id', '=', 'game_accounts.id_game_account')
            ->join('users', 'game_accounts.users_id', '=', 'users.id')
            ->join('games', 'scrims.games_id', '=', 'games.id')
            ->select('scrims.*', 'game_accounts.nickname', 'users.name', 'users.phone', 'users.email','users.avatar', 'games.name as game_name', 'games.picture')
            ->where('game_accounts.id_game_account', '=', $idGameAccount)
            ->get();
            if ($scrim->count() < '1') {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no scrim"
                ], 404);
            }
            foreach ($scrim as $value) {
                $result[] = [
                    'scrim' => [
                        'id' => $value->id,
                        'ranks_id' => $value->ranks_id,
                        'name_party' => $value->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'result' => $value->result,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'master-scrim' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'name' => $value->name,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'avatar' => $value->avatar
                    ],
                    'game-scrim' => [
                        'id' => $value->games_id,
                        'name' => $value->game_name,
                        'picture' => URL::to('/api/picture-game/'.$value->picture),
                    ]
                ];
            }
            return response()->json([
                "status" => "success",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    public function getScrimByIdGame($idGame)
    {
        try {
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == "3") {
                return response()->json([
                    "status" => "error",
                    "message" => "It's not your role"
                ], 403);
            }
            $scrim = $this->scrim->join('game_accounts', 'scrims.game_accounts_id', '=', 'game_accounts.id_game_account')
            ->join('users', 'game_accounts.users_id', '=', 'users.id')
            ->join('games', 'scrims.games_id', '=', 'games.id')
            ->select('scrims.*', 'game_accounts.nickname', 'users.name', 'users.phone', 'users.email', 'users.avatar', 'games.name as game_name', 'games.picture')
            ->where('games.id', '=', $idGame)
            ->get();
            if ($scrim->count() < '1') {
                return response()->json([
                    "status" => "error",
                    "message" => "There is no scrim"
                ], 404);
            }
            foreach ($scrim as $value) {
                $result[] = [
                    'scrim' => [
                        'id' => $value->id,
                        'ranks_id' => $value->ranks_id,
                        'name_party' => $value->name_party,
                        'image' => URL::to('/api/picture-scrim/'.$value->image),
                        'quota' => $value->quota,
                        'scrim_system' => $value->scrim_system,
                        'scrim_date' => $value->scrim_date,
                        'status' => $value->status,
                        'result' => $value->result,
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'master-scrim' => [
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'name' => $value->name,
                        'phone' => $value->phone,
                        'email' => $value->email,
                        'avatar' => $value->avatar
                    ],
                    'game-scrim' => [
                        'id' => $value->games_id,
                        'name' => $value->game_name,
                        'picture' => URL::to('/api/picture-game/'.$value->picture),
                    ]
                ];
            }
            return response()->json([
                "status" => "success",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}
