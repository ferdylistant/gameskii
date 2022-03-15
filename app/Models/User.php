<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Notifications\MailVerifyNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, HasFactory, HasApiTokens, CanResetPassword, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'fb',
        'ig',
        'provinsi',
        'kabupaten',
        'kecamatan',
        'tgl_lahir',
        'avatar',
        'ip_address',
        'last_login',
        'google_id',
        'email_verified_at'
    ];
    protected $hidden = [
        'id','password', 'remember_token', 'is_verified', 'roles_id'
    ];
    public function sendPasswordResetNotification($token)
    {
        $url = URL::to('/api/reset-password?token=' . $token);
        $this->notify(new ResetPasswordNotification($url));
    }
    // public function sendEmailVerificationNotification($token)
    // {
    //     $url = 'http://api.gameski.com/api/email-verification?token=' . $token;
    //     $this->notify(new MailVerifyNotification($url));
    // }
}
