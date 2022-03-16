<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
class ImageSponsorTournament extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasApiTokens;

    protected $table = 'image_sponsor_tournaments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'tournaments_id', 'image'
    ];
    protected $hidden = [
        'id',
    ];
    public function tournament()
    {
        return $this->belongsTo('App\Models\Tournament', 'tournaments_id');
    }
    public function saveUploads($id,$imageName)
    {
        return $this->create([
            'tournaments_id' => $id,
            'image' => $imageName
        ]);
    }
}
