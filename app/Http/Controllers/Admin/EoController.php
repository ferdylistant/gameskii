<?php

namespace App\Http\Controllers\Admin;

use App\Models\Game;
use App\Models\User;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\EORequestAcceptedNotification;

class EoController extends Controller
{
    public function __construct()
    {
        $this->user = new User();
        $this->game = new Game();
        $this->gameAccount = new GameAccount();
        $this->eo = new EoTournament();
    }
    public function getRequestEo(Request $request)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $dataEo = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->select('tournament_eos.*', 'game_accounts.nickname', 'users.avatar')
                ->where('tournament_eos.status', '=', '0')
                ->get();
            if ($dataEo->count() < 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not Found.'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => $dataEo
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptRequestEo(Request $request, $idEo)
    {
        try{
            $roles_id = auth('user')->user()->roles_id;
            if ($roles_id == '3') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to access this resource.'
                ], 401);
            }
            $dataEo = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->join('users', 'users.id', '=', 'game_accounts.users_id')
                ->select('tournament_eos.*', 'game_accounts.nickname','game_accounts.users_id', 'users.avatar')
                ->where('tournament_eos.id', '=', $idEo)
                ->where('tournament_eos.status', '=', '0')
                ->first();
            if ($dataEo == null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not Found.'
                ], 404);
            }
            $alreadyAccept = $this->eo->where('id', '=', $idEo)
                ->where('status', '=', '1')
                ->first();
            if ($alreadyAccept) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This request has already been accepted.'
                ], 400);
            }
            $verified_at = Carbon::now('Asia/Jakarta');
            $dataEo->status = '1';
            $dataEo->verified_at = $verified_at->toDateTimeString();
            if ($dataEo->save()){
                $details = [
                    'id' => $dataEo->id,
                    'game_accounts_id' => $dataEo->game_accounts_id,
                    'nickname' => $dataEo->nickname,
                    'avatar' => $dataEo->avatar,
                    'organization_name' => $dataEo->organization_name,
                    'organization_email' => $dataEo->organization_email,
                    'organization_phone' => $dataEo->organization_phone,
                    'provinsi' => $dataEo->provinsi,
                    'kabupaten' => $dataEo->kabupaten,
                    'kecamatan' => $dataEo->kecamatan,
                    'address' => $dataEo->address,
                    'verified_at' => $dataEo->verified_at,
                    'status' => $dataEo->status,
                    'message' => 'Request has been accepted by admin.',
                ];
                $user = $this->user->where('id', '=', $dataEo->users_id)->first();
                $user->notify(new EORequestAcceptedNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Eo Tournament has been accepted.'
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
