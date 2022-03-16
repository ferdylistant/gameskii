<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Tournament extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasApiTokens;

    protected $table = 'tournaments';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id','eo_id','games_id','ranks_id','name_tournament','tournament_system','bracket_type','play_date','quota','prize','result','picture'
    ];
    public function eo()
    {
        return $this->belongsTo('App\Models\Eo', 'eo_id');
    }
    public function games()
    {
        return $this->belongsTo('App\Models\Game', 'games_id');
    }
    public function ranks()
    {
        return $this->belongsTo('App\Models\Rank', 'ranks_id');
    }
    public function imageSponsorTournament()
    {
        return $this->hasMany('App\Models\ImageSponsorTournament', 'tournaments_id');
    }
}
