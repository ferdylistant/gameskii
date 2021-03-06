<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\GameAccount;
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
        $this->gameAccount = new GameAccount();
    }
    public function getGameData(Request $request)
    {
        $dataGame = $this->game->join('top_banner_games','games.id','=',"top_banner_games.games_id")
        ->join('bottom_banner_games','games.id','=',"bottom_banner_games.games_id")
        ->select('games.*','top_banner_games.path as top_banner','bottom_banner_games.path as bottom_banner')
        ->get();
        foreach ($dataGame as $value) {
            $data[] = [
                'id' => $value->id,
                'name' => $value->name,
                'picture' => URL::to('/api/picture-game/'.$value->picture),
                'top_banner' => URL::to('/api/banner-game/top/'.$value->top_banner),
                'bottom_banner' => URL::to('/api/banner-game/bottom/'.$value->bottom_banner),
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at
            ];
        }
        try {
            $arrayData = [
                'status' => 'success',
                'message' => 'Data Game',
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
    public function postGame(Request $request, $idGame)
    {
        $roles_id = auth('user')->user()->roles_id;
        if ($roles_id != '3') {
            return response()->json([
                "status" => "error",
                'message' => 'You are not authorized to access this resource'
            ], 403);
        }
        $game = $this->game->where('id', $idGame)->first();
        if (!$game) {
            return response()->json([
                "status" => "error",
                "message" => "Id game is wrong or game not found"
            ],404);
        }
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

        if ($this->gameAccount->where('users_id', '=', auth('user')->user()->id)->where('games_id', '=', $idGame)->count() != 0) {
            try {
                $this->gameAccount->where('users_id', '=', auth('user')->user()->id)->where('games_id', '=', $idGame)->update([
                    'is_online' => '1'
                ]);
                $dataGameAccount = $this->gameAccount->where('users_id', '=', auth('user')->user()->id)->where('games_id', '=', $idGame)->first();
                $request->session()->put('gamedata', $data);
                $request->session()->put('game_account', $dataGameAccount);
                return response()->json([
                    'status' => 'logged',
                    'message' => 'You already have an account for this game',
                    'data' => [
                        'game-account-data' => $dataGameAccount,
                        'game-data' => $data
                    ],

                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }
        $request->session()->put('gamedata', $data);
        return response()->json([
            'status' => 'created',
            'message' => 'You will be redirected to the registration game account page',
            'data' => [
                'game-data' => $data
            ]
        ], 201);
    }

    public function create(Request $request)
    {
        $role = auth('user')->user()->roles_id;
        // return response()->json($role);
        if (($role != '1' && $role != '2')) {
            return response()->json([
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
                foreach ($dataFile as $value) {
                    $imageName = date('mdYHis') . $value->hashName();
                    $value->move(storage_path('uploads/banner-game/top'), $imageName);
                    $topBannerGame = new TopBannerGame();
                    $path['path'] = $imageName;
                }
                $topBannerGame->path = $path['path'];
            }
            if ($request->hasFile('bottom_banner')) {
                $dataFile = $request->file('bottom_banner');
                foreach ($dataFile as $value) {
                    $imageName = date('mdYHis') . $value->hashName();
                    $value->move(storage_path('uploads/banner-game/bottom'), $imageName);
                    $bottomBannerGame = new BottomBannerGame();
                    $path['path'] = $imageName;
                }
                $bottomBannerGame->path = $path['path'];
            }

            $this->game->id = Uuid::uuid4()->toString();
            $this->game->name = $request->name;
            if ($this->game->save()) {
                $games_id = $this->game->id;
                // return response()->json($this->game);
                $topBannerGame->games_id = $games_id;
                $bottomBannerGame->title = $request->title_bottom_banner;
                $bottomBannerGame->games_id = $games_id;
                $topBannerGame->save();
                $bottomBannerGame->save();
                return response()->json([
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
        $role = auth('user')->user()->roles_id;
        if (($role != '1' && $role != '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        //validasi form
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:15|unique:games,name',
            'picture' => 'required|file|max:3048|image',
            'top_banner.*' => 'required|file|max:5048|image',
            'bottom_banner.*' => 'required|file|max:5048|image',
            'title_bottom_banner.*' => 'required|max:20'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }
        try {
            $gameData = $this->game->join('top_banners', 'top_banners.games_id', '=', 'games.id')
            ->join('bottom_banners', 'bottom_banners.games_id', '=', 'games.id')
            ->where('games.id', '=', $id)
            ->select('games.*', 'top_banners.path as top_banner', 'bottom_banners.path as bottom_banner', 'bottom_banners.title as title_bottom_banner')
            ->first();

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
            if ($request->hasFile('top_banner')) {
                $dataFile = $request->file('top_banner');
                $imageName = date('mdYHis') . $dataFile->hashName();

                $current_image_path = storage_path('uploads/banner-game/top/'.$gameData->top_banner);
                if (file_exists($current_image_path)) {
                    File::delete($current_image_path);
                }
                $dataFile->move(storage_path('uploads/banner-game/top'), $imageName);
                $gameData->path      = $imageName;
            }
            if ($request->has('name')) {
                $gameData->name      = $request->name;
            }
            if ($gameData->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Game updated successfully!'
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
