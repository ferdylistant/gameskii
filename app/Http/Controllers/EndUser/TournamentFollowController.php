<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Rank;
use App\Models\Tournament;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Models\TournamentFollow;
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
                ], 401);
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
                ], 401);
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
                ], 401);
            }
            $tournamentFollow = $this->tournamentFollow->where('tournaments_id',$idTournament)
                ->where('game_accounts_id',$sessGameAccount->id_game_account)
                ->first();
            if ($tournamentFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already followed this tournament.'
                ], 401);
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
                ], 401);
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
                ], 401);
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
                ], 401);
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
}
