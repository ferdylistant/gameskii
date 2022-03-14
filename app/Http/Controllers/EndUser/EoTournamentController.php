<?php

namespace App\Http\Controllers\EndUser;

use App\Models\Game;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\GameAccount;
use App\Models\EoTournament;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EORequestNotification;

class EoTournamentController extends Controller
{
    public function __construct()
    {
        $this->eo = new EoTournament();
        $this->game = new Game();
        $this->user = new User();
        $this->gameAccount = new GameAccount();
    }
    public function registrationEo(Request $request)
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
                ], 408);
            }
            $dataEo = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->where('tournament_eos.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->where('game_accounts.games_id', '=', $sessGame['game']['id'])
                ->where('game_accounts.users_id', '=', auth('user')->user()->id)
                ->where('tournament_eos.status', '=', '1')
                ->first();

            if ($dataEo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already registered as an EO.'
                ], 403);
            }
            $dataEoWaiting = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                ->where('tournament_eos.game_accounts_id', '=', $sessGameAccount->id_game_account)
                ->where('game_accounts.games_id', '=', $sessGame['game']['id'])
                ->where('game_accounts.users_id', '=', auth('user')->user()->id)
                ->where('tournament_eos.status', '=', '0')
                ->first();

            if ($dataEoWaiting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have requested to be an EO. Please wait for the admin to approve your request.'
                ], 403);
            }
            $validator = Validator::make($request->all(), [
                'organization_name' => 'required|string|max:50',
                'organization_email' => 'required|string|email|max:50',
                'organization_phone' => 'required|min:11|string',
                'province' => 'required|string|max:30',
                'city' => 'required|string|max:30',
                'district' => 'required|string|max:30',
                'address' => 'required|string|max:100',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }
            $dataEo = $this->eo->create([
                'id' => Uuid::uuid4()->toString(),
                'game_accounts_id' => $sessGameAccount->id_game_account,
                'organization_name' => $request->organization_name,
                'organization_email' => $request->organization_email,
                'organization_phone' => $request->organization_phone,
                'provinsi' => $request->province,
                'kabupaten' => $request->city,
                'kecamatan' => $request->district,
                'address' => $request->address,
                'status' => '0',
            ]);
            if ($dataEo) {
                $dataDetail = $this->eo->join('game_accounts', 'game_accounts.id_game_account', '=', 'tournament_eos.game_accounts_id')
                    ->join('users', 'users.id', '=', 'game_accounts.users_id')
                    ->where('tournament_eos.game_accounts_id', '=', $sessGameAccount->id_game_account)
                    ->where('game_accounts.games_id', '=', $sessGame['game']['id'])
                    ->where('game_accounts.users_id', '=', auth('user')->user()->id)
                    ->where('tournament_eos.status', '=', '0')
                    ->where('tournament_eos.id', '=', $dataEo->id)
                    ->first();
                $details = [
                    'id' => $dataEo->id,
                    'organization_name' => $dataEo->organization_name,
                    'organization_email' => $dataEo->organization_email,
                    'organization_phone' => $dataEo->organization_phone,
                    'provinsi' => $dataEo->provinsi,
                    'kabupaten' => $dataEo->kabupaten,
                    'kecamatan' => $dataEo->kecamatan,
                    'address' => $dataEo->address,
                    'status' => $dataEo->status,
                    'avatar' => $dataDetail->avatar,
                    'game_accounts_id' => $dataEo->game_accounts_id,
                    'games_id' => $sessGame['game']['id'],
                    'users_id' => auth('user')->user()->id,
                    'message' => $dataDetail->nickname . ' has requested to be an EO. Please confirm the request.',
                    'created_at' => $dataEo->created_at,
                    'updated_at' => $dataEo->updated_at,
                ];
                $userNotify = $this->user->where('roles_id', '=', '1')->where('roles_id', '=', '2')->get();
                foreach ($userNotify as $administrator) {
                    $administrator->notify(new EORequestNotification($details));
                }
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your request has been sent. Please wait for the admin to approve your request.'
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
