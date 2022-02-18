<?php

namespace App\Models;

use App\Models\Follow;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class GameAccount extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, HasApiTokens;

    protected $table = 'game_accounts';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id','id_game_account', 'nickname','games_id'
    ];
    protected $hidden = [
        'users_id'
    ];
    // public function follow(GameAccount $user) {
    //     if(!$this->isFollowing($user)) {
    //         Follow::create([
    //             'game_account_id' => auth()->id(),
    //             'following_id' => $user->id
    //         ]);
    //     }
    // }

    // public function unfollow(User $user) {
    //     Follow::where('game_account_id', auth('user')->id())->where('following_id', $user->id)->delete();
    // }

    // public function isFollowing(GameAccount $user) {
    //     return $this->following()->where('game_accounts.id', $user->id)->exists();
    // }

    // public function following() {
    //     return $this->hasManyThrough(GameAccount::class, Follow::class, 'game_account_id', 'id', 'id', 'following_id');
    // }

    // public function followers() {
    //     return $this->hasManyThrough(GameAccount::class, Follow::class, 'following_id', 'id', 'id', 'game_account_id');
    // }
}
