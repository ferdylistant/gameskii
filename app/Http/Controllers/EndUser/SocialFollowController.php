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
    public function addFriend(Request $request, $idGameAccount)
    {
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        // return response()->json($dd['game']['id']);
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        try {
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'code' => 404,
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
                    'code' => 200,
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
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        try {
            $sessGameAccount = $request->session()->get('game_account');
            $data = $this->follow->where('game_accounts_id', '=', $sessGameAccount->id_game_account)
            ->where('status_follow', '=', '1')
            ->get();
            if ($data == '[]') {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => "You don't have any friend"
                ], 404);
            }
            foreach ($data as $value) {
                $result[] = $this->gameAccount->where('id', '=', $value->acc_following_id)->get();
            }
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'total' => count($result),
                'data' => $result
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
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        try {
            $sessGameAccount = $request->session()->get('game_account');
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'code' => 404,
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
                'code' => 200,
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
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        try {
            $sessGameAccount = $request->session()->get('game_account');
            // return response()->json($sessGameAccount);
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            return response()->json($dataFollowing);
            $alreadyFollowed = $this->follow->where('acc_following_id', '=', $dataFollowing->id)->first();
            if ($alreadyFollowed) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => "You already followed this account"
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
                'code' => 200,
                'status' => 'success',
                'message' => 'Friend accept successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function rejectFriend(Request $request, $idGameAccount)
    {
        $sessGame = $request->session()->get('gamedata');
        $role = auth('user')->user()->roles_id;
        if (($role == '1' || $role == '2')) {
            return response()->json([
                "code" => 403,
                "status" => "error",
                "message" => "It's not your role"
            ], 403);
        }
        try {
            $sessGameAccount = $request->session()->get('game_account');
            $dataFollowing = $this->gameAccount->where('id_game_account', '=', $idGameAccount)
            ->where('games_id', '=', $sessGame['game']['id'])
            ->first();
            if (!$dataFollowing) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => "Game account data not found"
                ], 404);
            }
            $statusFollow = $this->follow->where('game_accounts_id', '=', $dataFollowing->id_game_account)
            ->where('status_follow', '=', '1')->first();
            if ($statusFollow) {
                return response()->json([
                    'code' => 404,
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
                'code' => 200,
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
