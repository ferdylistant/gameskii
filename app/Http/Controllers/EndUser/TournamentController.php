<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Game;
use App\Models\User;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TournamentController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->eo = new EoTournament();
        $this->tournament = new Tournament();
        $this->gameAccount = new GameAccount();
    }
    public function createTournament (Request $request)
    {
        try{
            $role_id = auth('user')->user()->roles_id;
            if ($role_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 408);
            }
            $verifiedEo = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'games.id', '=', 'game_accounts.games_id')
                ->where('tournament_eos.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->where('game_accounts.games_id', '=', $sessGame['game']['id'])
                ->select('tournament_eos.*', 'users.avatar' ,'game_accounts.nickname', 'game_accounts.games_id', 'games.name as game_name')
                ->first();
            if (!$verifiedEo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not registered as an EO to create a tournament.'
                ], 403);
            }
            $validator = Validator::make($request->all(), [
                'name_tournament' => 'required|string|max:255',
                'ranks_id' => 'required|integer',
                'tournament_system' => 'required',
                'bracket_type' => 'required',
                'play_date' => 'required|date_format:Y-m-d H:i:s|after:24 hours',
                'quota' => 'required|integer',
                'prize' => 'required|integer',
                'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'sponsor_img.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $this->tournament->id = Uuid::uuid4()->toString();
            $this->tournament->name_tournament = $request->name_tournament;
            $this->tournament->eo_id = $verifiedEo->id;
            $this->tournament->games_id = $verifiedEo->games_id;
            $this->tournament->ranks_id = $request->ranks_id;
            $this->tournament->tournament_system = $request->tournament_system;
            $this->tournament->bracket_type = $request->bracket_type;
            $this->tournament->play_date = $request->play_date;
            $this->tournament->quota = $request->quota;
            $this->tournament->prize = $request->prize;
            if($request->hasFile('picture')) {
                $dataFile = $request->file('picture');
                $imageName = date('mdYHis') . $dataFile->hashName();
                $dataFile->move(storage_path('uploads/picture-tournament'), $imageName);
                $this->tournament->picture = $imageName;
            }
            if($request->hasFile('sponsor_img')) {
                $dataFile = $request->file('sponsor_img');
                foreach ($dataFile as $value) {
                    $imageName = date('mdYHis') . $value->hashName();
                    foreach ($request->sponsor_img as $storage) {
                        $storage->move(storage_path('uploads/sponsor-tournament'), $imageName);
                        $this->tournament->image = $imageName;
                    }
                }
            }
            if ($this->tournament->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tournament created successfully.'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
