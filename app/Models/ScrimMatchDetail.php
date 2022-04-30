<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class ScrimMatchDetail extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens, Authenticatable, Authorizable;

    protected $table = 'scrim_match_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'scrims_id',
        'teams1_id',
        'teams2_id',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [
        'id',
    ];
}
