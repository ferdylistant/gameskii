<?php

namespace App\Http\Controllers\EndUser;

use Carbon\Carbon;
use App\Models\Game;
use App\Models\Rank;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\ImageSponsorTournament;
use Illuminate\Support\Facades\Validator;

class TournamentController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->eo = new EoTournament();
        $this->tournament = new Tournament();
        $this->tournamentMatch = new TournamentMatch();
        $this->gameAccount = new GameAccount();
        $this->rank = new Rank();
        $this->imageSponsor = new ImageSponsorTournament();
    }
    public function createTournament (Request $request)
    {
        try{
            $role_id = auth('user')->user()->roles_id;
            if ($role_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
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
            $distanceCreate = $this->tournament->where('games_id', '=', $sessGame['game']['id'])
            ->where('eo_id', '=', $verifiedEo->id)->first();
            if ($distanceCreate){
                $dateCreated = new Carbon($distanceCreate->created_at, 'Asia/Jakarta');
                $diffDays = $dateCreated->isToday();
                if ($diffDays) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can create tournament only once a day',
                    ], 403);
                }
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
            if ($this->tournament->save()) {
                $dataFile = $request->file('sponsor_img');
                // $fileName = '';
                foreach ($dataFile as $value) {
                    $imageName = date('mdYHis') . $value->hashName();
                    $value->move(storage_path('uploads/sponsor-tournament'), $imageName);
                    $imageSponsor = new ImageSponsorTournament();
                    $image['image'] = $imageName;
                }
                $imageSponsor->image = $image['image'];
                $imageSponsor->tournaments_id = $this->tournament->id;
                if ($imageSponsor->save()){
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Tournament created successfully',
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTournaments(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 408);
            }
            $dataTournament = $this->tournament->join('image_sponsor_tournaments', 'image_sponsor_tournaments.tournaments_id', '=', 'tournaments.id')
                ->join('tournament_eos', 'tournament_eos.id', '=', 'tournaments.eo_id')
                ->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'games.id', '=', 'tournaments.games_id')
                ->where('tournaments.games_id', '=', $sessGame['game']['id'])
                ->select('tournaments.*',
                'tournament_eos.id as id_tournament_eo','tournament_eos.organization_name','tournament_eos.organization_email','tournament_eos.organization_phone',
                'tournament_eos.provinsi','tournament_eos.kabupaten','tournament_eos.kecamatan','tournament_eos.address',
                'image_sponsor_tournaments.image','users.avatar' ,'game_accounts.nickname', 'games.name as game_name')
                ->get();
            if ($dataTournament->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tournament not found.'
                ], 404);
            }
            foreach ($dataTournament as $value) {
                $data[] = [
                    'tournament' => [
                        'id' => $value->id,
                        'name_tournament' => $value->name_tournament,
                        'ranks' => $this->rank->where('id', $value->ranks_id)->select('class')->first(),
                        'tournament_system' => $value->tournament_system,
                        'bracket_type' => $value->bracket_type,
                        'play_date' => $value->play_date,
                        'team_play' => $this->tournamentMatch->where('tournaments_id','=', $value->id)->get()->count(),
                        'quota' => $value->quota,
                        'prize' => $value->prize,
                        'result' => $value->result,
                        'picture' => URL::to('/api/picture-tournament/'.$value->picture),
                        'sponsor_img' => URL::to('/api/picture-sponsor-tournament/'.$value->image),
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'eo' => [
                        'id_tournament_eo' => $value->id_tournament_eo,
                        'organization_name' => $value->organization_name,
                        'organization_email' => $value->organization_email,
                        'organization_phone' => $value->organization_phone,
                        'provinsi' => $value->provinsi,
                        'kabupaten' => $value->kabupaten,
                        'kecamatan' => $value->kecamatan,
                        'address' => $value->address,
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'avatar' => $value->avatar,
                        'game_name' => $value->game_name,
                    ]
                    ];
            }
            return response()->json([
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
    public function getMyTournaments(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id != '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 403);
            }
            $sessGame = $request->session()->get('gamedata');
            $sessGameAccount = $request->session()->get('game_account');
            if ($sessGame == null || $sessGameAccount == null) {
                $game_account = $this->gameAccount->where('users_id',auth('user')->user()->id)->first();
                $game_account->is_online = 0;
                $game_account->save();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session timeout. Please login again.'
                ], 408);
            }
            $verifiedEo = $this->eo->where('game_accounts_id', '=',$sessGameAccount->id_game_account)
            ->where('status', '=', '1')
            ->first();
            if (!$verifiedEo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not EO.'
                ], 403);
            }
            $dataTournament = $this->tournament->join('tournament_eos', 'tournament_eos.id', '=', 'tournaments.eo_id')
                ->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'games.id', '=', 'tournaments.games_id')
                ->join('image_sponsor_tournaments', 'image_sponsor_tournaments.tournaments_id', '=', 'tournaments.id')
                ->where('tournaments.games_id', $sessGame['game']['id'])
                ->where('tournaments.eo_id', $verifiedEo->id)
                ->select('tournaments.*',
                'tournament_eos.id as id_tournament_eo','tournament_eos.organization_name','tournament_eos.organization_email','tournament_eos.organization_phone',
                'tournament_eos.provinsi','tournament_eos.kabupaten','tournament_eos.kecamatan','tournament_eos.address',
                'image_sponsor_tournaments.image','users.avatar' ,'game_accounts.nickname', 'games.name as game_name')
                ->get();
            // return response()->json($dataTournament);
            if ($dataTournament->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tournament not found.'
                ], 404);
            }
            foreach ($dataTournament as $value) {
                $data[] = [
                    'tournament' => [
                        'id' => $value->id,
                        'name_tournament' => $value->name_tournament,
                        'ranks' => $this->rank->where('id', $value->ranks_id)->select('class')->first(),
                        'tournament_system' => $value->tournament_system,
                        'bracket_type' => $value->bracket_type,
                        'play_date' => $value->play_date,
                        'team_play' => $this->tournamentMatch->where('tournaments_id','=', $value->id)->get()->count(),
                        'quota' => $value->quota,
                        'prize' => $value->prize,
                        'result' => $value->result,
                        'picture' => URL::to('/api/picture-tournament/'.$value->picture),
                        'sponsor_img' => URL::to('/api/picture-sponsor-tournament/'.$value->image),
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at
                    ],
                    'eo' => [
                        'id_tournament_eo' => $value->id_tournament_eo,
                        'organization_name' => $value->organization_name,
                        'organization_email' => $value->organization_email,
                        'organization_phone' => $value->organization_phone,
                        'provinsi' => $value->provinsi,
                        'kabupaten' => $value->kabupaten,
                        'kecamatan' => $value->kecamatan,
                        'address' => $value->address,
                        'game_accounts_id' => $value->game_accounts_id,
                        'nickname' => $value->nickname,
                        'avatar' => $value->avatar,
                        'game_name' => $value->game_name,
                    ]
                    ];
            }
            return response()->json([
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
}
