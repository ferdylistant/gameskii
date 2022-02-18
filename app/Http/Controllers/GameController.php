<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use App\Models\TopBannerGame;
use App\Models\BottomBannerGame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    public function __construct()
    {
        $this->game = new Game();
        $this->user = new User();
        $this->topBanner = new TopBannerGame();
        $this->bottomBanner = new BottomBannerGame();
    }
    public function getGameData(Request $request)
    {
        $dataGame = $this->game->all();
        $dataJoin = DB::table('users')
            ->select('users.email', 'roles.name')
            ->join('games', 'users.id', '=', 'games.user_id')
            ->join('roles', 'roles.id', '=', 'users.roles_id')
            ->where('games.user_id', $dataGame[0]->user_id)
            ->get();
        $email = $this->user->where('email', $dataJoin[0]->email)->first();
        try {
            $arrayData = [
                'code' => 200,
                'status' => 'success',
                'data' => [
                    'game'=>[
                        'name' => $dataGame[0]->name,
                        'picture' => URL::to('/api/picture-game/'.$dataGame[0]->picture),
                        'banner' => URL::to('/api/banner-game/'.$dataGame[0]->banner),
                        'created_at' => $dataGame[0]->created_at,
                        'updated_at' => $dataGame[0]->updated_at,
                    ],
                    'by' =>[
                        'name' => $email->name,
                        'role' => $dataJoin[0]->name
                    ]
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
    public function postGame(Request $request, $idGame)
    {
        try {
            $game = $this->game->where('id', $idGame)->first();
            $topBanner = $this->topBanner->where('games_id', $game->id)->get();
            $bottomBanner = $this->bottomBanner->where('games_id', $game->id)->get();
            foreach ($topBanner as $result) {
                $top[] = URL::to('/api/banner-game/top/'.$result->path);
            }
            foreach ($bottomBanner as $value) {
                $title[] = $value->title;
                $bottom[] = URL::to('/api/banner-game/bottom/'.$value->path);
            }
            $data = [
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                    'picture' => URL::to('/api/picture-game/'.$game->picture),
                    'created_at' => $game->created_at,
                    'updated_at' => $game->updated_at
                ],
                'top-banner' => [
                    'url' => $top,
                ],
                'bottom-banner' => [
                    'title' => $title,
                    'url' => $bottom
                ]

            ];
            $request->session()->put('gamedata', $data);
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function create(Request $request)
    {
        $role = auth('user')->user()->roles_id;
        // return response()->json($role);
        if (($role != '1' && $role != '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        //validasi form register
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:15|unique:games,name',
            'picture' => 'required|file|max:3048|image',
            'top_banner.*' => 'required|file|max:5048|image',
            'bottom_banner.*' => 'required|file|max:5048|image',
            'title_bottom_banner.*' => 'required|max:20'
        ]);
        //jika validasi eror
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 409);
        }
        try {
            // return response()->json($request->get('top_banner'));
            if ($request->hasFile('picture')) {
                $dataFile = $request->file('picture');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $dataFile->move(storage_path('uploads/picture-game'), $imageName);
                $this->game->picture         = $imageName;
            }
            if ($request->hasFile('top_banner')) {
                $dataFile = $request->file('top_banner');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $dataFile->move(storage_path('uploads/banner-game/top'), $imageName);
                $this->topBanner->path     = $imageName;
            }
            if ($request->hasFile('bottom_banner')) {
                $dataFile = $request->file('bottom_banner');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $dataFile->move(storage_path('uploads/banner-game/bottom'), $imageName);
                $this->bottomBanner->path     = $imageName;
            }

            $this->game->id = Uuid::uuid4()->toString();
            $this->game->name = $request->name;
            if ($this->game->save()) {
                $games_id = $this->game->id;
                // return response()->json($this->game);
                $this->topBanner->games_id = $games_id;
                $this->bottomBanner->title = $request->title_bottom_banner;
                $this->bottomBanner->games_id = $games_id;
                $this->topBanner->save();
                $this->bottomBanner->save();
                return response()->json([
                    'code' => 201,
                    'status' => 'posted',
                    'message' => 'Game has been posted successfully!'
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function update(Request $request, $id)
    {
        //validasi form
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:15',
            'picture' => 'file|max:3048|image',
            'banner' => 'file|max:5048|image',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        try {
            $gameData = $this->game->findOrFail($id);

            if ($request->hasFile('picture')) {
                $dataFile = $request->file('picture');
                $imageName = date('mdYHis') . $dataFile->hashName();

                $current_image_path = storage_path('uploads/picture-game/'.$gameData->picture);
                if (file_exists($current_image_path)) {
                    File::delete($current_image_path);
                }
                $dataFile->move(storage_path('uploads/picture-game'), $imageName);
                $gameData->picture      = $imageName;
            }
            if ($request->hasFile('banner')) {
                $dataFile = $request->file('banner');
                $imageName = date('mdYHis') . $dataFile->hashName();

                $current_image_path = storage_path('uploads/banner-game/'.$gameData->banner);
                if (file_exists($current_image_path)) {
                    File::delete($current_image_path);
                }
                $dataFile->move(storage_path('uploads/banner-game'), $imageName);
                $gameData->banner      = $imageName;
            }
            if ($request->has('name')) {
                $gameData->name      = $request->name;
            }
            $gameData->user_id = auth('user')->user()->id;
            if ($gameData->save()) {
                return response()->json([
                    'code' => 201,
                    'status' => 'success',
                    'message' => 'Game updated successfully!'
                ], 201);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
