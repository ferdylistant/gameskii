<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Models\TournamentFollow;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

class TournamentFollowController extends Controller
{
    public function __construct()
    {
        $this->tournamentFollow = new TournamentFollow();
        $this->tournament = new Tournament();
        $this->gameAccount = new GameAccount();
        $this->eo = new EoTournament();
        $this->rank = new Rank();
    }
    public function followTournament(Request $request,$idTournament)
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
            $tournament = $this->tournament->where('id',$idTournament)->first();
            if (!$tournament) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tournament not found.'
                ], 404);
            }
            $myTournament = $this->tournament->join('tournament_eos', 'tournament_eos.id', '=', 'tournaments.eo_id')
                ->where('tournaments.id', '=', $idTournament)
                ->where('tournament_eos.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->where('tournament_eos.status', '=', '1')
                ->first();
            if ($myTournament) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This is your tournament. You can not follow it.'
                ], 403);
            }
            $tournamentFollow = $this->tournamentFollow->where('tournaments_id',$idTournament)
                ->where('game_accounts_id',$sessGameAccount->id_game_account)
                ->first();
            if ($tournamentFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already followed this tournament.'
                ], 403);
            }
            $this->tournamentFollow->tournaments_id = $idTournament;
            $this->tournamentFollow->game_accounts_id = $sessGameAccount->id_game_account;
            if ($this->tournamentFollow->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You have successfully followed this tournament.'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function unfollowTournament(Request $request,$idTournament)
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
            $tournament = $this->tournament->where('id',$idTournament)->first();
            if (!$tournament) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tournament not found.'
                ], 404);
            }
            $tournamentFollow = $this->tournamentFollow->where('tournaments_id',$idTournament)
                ->where('game_accounts_id',$sessGameAccount->id_game_account)
                ->first();
            if (!$tournamentFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have not followed this tournament.'
                ], 403);
            }
            if ($tournamentFollow->delete()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'You have successfully unfollowed this tournament.'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getTournamentFollowed(Request $request)
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
            $tournamentFollow = $this->tournamentFollow->join('tournaments', 'tournaments.id', '=', 'tournament_follows.tournaments_id')
                ->join('tournament_eos', 'tournament_eos.id', '=', 'tournaments.eo_id')
                ->join('image_sponsor_tournaments', 'image_sponsor_tournaments.tournaments_id', '=', 'tournaments.id')
                ->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->join('games', 'games.id', '=', 'tournaments.games_id')
                ->where('tournament_follows.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->where('tournaments.games_id', '=', $sessGame['game']['id'])
                ->select('tournaments.*',
                'tournament_eos.id as id_tournament_eo',
                'tournament_eos.organization_name',
                'tournament_eos.organization_email',
                'tournament_eos.organization_phone',
                'tournament_eos.provinsi',
                'tournament_eos.kabupaten',
                'tournament_eos.kecamatan',
                'tournament_eos.address',
                'users.avatar',
                'game_accounts.nickname',
                'image_sponsor_tournaments.image',
                'games.name as game_name')->get();
            if ($tournamentFollow->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have not followed any tournament.'
                ], 404);
            }
            foreach ($tournamentFollow as $value) {
                $result[] = [
                    'tournament' => [
                        'id' => $value->id,
                        'name_tournament' => $value->name_tournament,
                        'ranks' => $this->rank->where('id',$value->ranks_id)->select('class')->first(),
                        'tournament_system' => $value->tournament_system,
                        'bracket_type' => $value->bracket_type,
                        'play_date' => $value->play_date,
                        'quota' => $value->quota,
                        'prize' => $value->prize,
                        'picture' => URL::to('/api/picture-tournament/'.$value->picture),
                        'sponsor_img' => URL::to('/api/picture-sponsor-tournament/'.$value->image),
                        'created_at' => $value->created_at,
                        'updated_at' => $value->updated_at,
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
                        'game_name' => $value->game_name
                    ]
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
}
