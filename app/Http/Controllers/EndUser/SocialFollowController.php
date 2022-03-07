<?php

namespace App\Http\Controllers\EndUser;

use App\Models\User;
use App\Models\GameAccount;
use App\Models\SocialFollow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\SocialFollowNotification;
use App\Notifications\SocialAcceptFriendNotification;

class SocialFollowController extends Controller
{
    public function __construct()
    {
        $this->gameAccount = new GameAccount();
        $this->follow = new SocialFollow();
        $this->user = new User();
    }
    public function getListFriendRequest(Request $request)
    {
        try {
            $user = auth('user')->user();
            $idGameAccount = $this->gameAccount->where('users_id', $user->id)->first();
            $listFriendRequest = $this->follow->where('game_accounts_id', $idGameAccount->id_game_account)
            ->where('acc_following_id', '=', NULL)
            ->where('status_follow', '=', '0')->get();
            if ($listFriendRequest->count() < '1') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'List friend request not found'
                ], 404);
            }
            foreach ($listFriendRequest as $value) {
                $listFriendRequest[] = $this->gameAccount->where('id', $value->acc_followers_id)->first();
            }
            return response()->json([
                'status' => 'success',
                'message' => 'List friend request',
                'data' => $listFriendRequest
            ],200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function addFriend(Request $request, $idGameAccount)
    {
        $role = auth('user')->user()->roles_id;
        // return response()->json($dd['game']['id']);
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        if ($sessGame == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            $userFollowing = $this->user->where('id', '=', $dataFollowing->users_id)->first();
            $sessGameAccount = $request->session()->get('game_account');

            // return response()->json($sessGameAccount->id);
            $details = [
                'id' => $sessGameAccount->id,
                'id_game_account' => $sessGameAccount->id_game_account,
                'nickname' => $sessGameAccount->nickname,
                'game_id' => $sessGame['game']['id'],
                'game_name' => $sessGame['game']['name'],
                'user_id' => auth('user')->user()->id,
                'user_name' => auth('user')->user()->name,
                'user_email' => auth('user')->user()->email,
                'user_avatar' => auth('user')->user()->avatar,
                'following_date' => date('Y-m-d H:i:s'),
                'message' => 'You have a new follower'
            ];
            // return response()->json($details);
            $this->follow->game_accounts_id = $sessGameAccount->id_game_account;
            $this->follow->acc_following_id = $dataFollowing->id;
            if ($this->follow->save()) {
                $followers = new SocialFollow();
                $followers->game_accounts_id = $dataFollowing->id_game_account;
                $followers->acc_followers_id = $sessGameAccount->id;
                $followers->save();
                $userFollowing->notify(new SocialFollowNotification($details));
                return response()->json([
                    'status' => 'success',
                    'message' => 'Following successfully'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function getFriends(Request $request)
    {
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        if ($sessGame == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $sessGameAccount = $request->session()->get('game_account');
            $data = $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status_follow', '=', '1')
            ->get();
            if ($data == '[]') {
                return response()->json([
                    'status' => 'error',
                    'message' => "You don't have any friend"
                ], 404);
            }
            foreach ($data as $value) {
                $result[] = $this->gameAccount->where('id', '=', $value->acc_following_id)->first();
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get friends successfully',
                'data' => [
                    'quantity' => count($result),
                    'data-friend' => $result
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function unfollow(Request $request, $idGameAccount)
    {
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('acc_following_id', '=', $dataFollowing->id)
            ->delete();
            $this->follow->where('game_accounts_id', '=', $dataFollowing->id_game_account)
            ->where('acc_followers_id', '=', $sessGameAccount->id)
            ->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Unfollow successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function acceptFriend(Request $request, $idGameAccount)
    {
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            // return response()->json($sessGameAccount);
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            $alreadyFollowed = $this->follow->where('acc_following_id', '=', $dataFollowing->id)
            ->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->count();
            if ($alreadyFollowed > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You already followed this account"
                ], 409);
            }
            $user = $this->user->where('id', '=', $dataFollowing->users_id)->first();
            $details = [
                'id' => $sessGameAccount->id,
                'id_game_account' => $sessGameAccount->id_game_account,
                'nickname' => $sessGameAccount->nickname,
                'game_id' => $sessGame['game']['id'],
                'game_name' => $sessGame['game']['name'],
                'user_id' => auth('user')->user()->id,
                'user_name' => auth('user')->user()->name,
                'user_email' => auth('user')->user()->email,
                'user_avatar' => auth('user')->user()->avatar,
                'following_date' => date('Y-m-d H:i:s'),
                'message' => 'You are now friend with ' . $sessGameAccount->nickname,
            ];
            $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('acc_followers_id', '=', $dataFollowing->id)
            ->update(['acc_following_id' => $dataFollowing->id,'status_follow' => '1']);
            $this->follow->where('game_accounts_id', '=', $dataFollowing->id_game_account)
            ->where('acc_following_id', '=', $sessGameAccount->id)
            ->update(['acc_followers_id' => $sessGameAccount->id,'status_follow' => '1']);
            $user->notify(new SocialAcceptFriendNotification($details));
            return response()->json([
                'status' => 'success',
                'message' => 'Friend accept successfully'
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectFriend(Request $request, $idGameAccount)
    {
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        $sessGame = $request->session()->get('gamedata');
        $sessGameAccount = $request->session()->get('game_account');
        if (($sessGame == null) || ($sessGameAccount == null)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session timeout'
            ], 408);
        }
        try {
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            $statusFollow = $this->follow->where('game_accounts_id', '=', $dataFollowing->id_game_account)
            ->where('status_follow', '=', '1')->first();
            if ($statusFollow) {
                return response()->json([
                    'status' => 'error',
                    'message' => "You are friend with this account"
                ], 404);
            }
            $user = $this->user->where('id', '=', $dataFollowing->users_id)->first();
            $details = [
                'id' => $sessGameAccount->id,
                'id_game_account' => $sessGameAccount->id_game_account,
                'nickname' => $sessGameAccount->nickname,
                'game_id' => $sessGame['game']['id'],
                'game_name' => $sessGame['game']['name'],
                'user_id' => auth('user')->user()->id,
                'user_name' => auth('user')->user()->name,
                'user_email' => auth('user')->user()->email,
                'user_avatar' => auth('user')->user()->avatar,
                'following_date' => date('Y-m-d H:i:s'),
                'message' => $sessGameAccount->nickname . ' rejected your friend request',
            ];
            $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('acc_following_id', '=', $dataFollowing->id)
            ->update(['status_follow' => '2']);
            $this->follow->where('game_accounts_id', '=', $dataFollowing->id_game_account)
            ->where('acc_followers_id', '=', $sessGameAccount->id)
            ->update(['status_follow' => '2']);
            $user->notify(new SocialRejectFriendNotification($details));
            return response()->json([
                'status' => 'success',
                'message' => 'Reject successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
