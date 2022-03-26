<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Events\LastLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LastLoginListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  LastLogin  $event
     * @return void
     */
    public function handle(LastLogin $event)
    {
        $current = Carbon::now('Asia/Jakarta');
        $userinfo = $event->user;
        $save = DB::table('users')
            ->where('id', $userinfo['data']['id'])
            ->update(['last_login' => $current->toDateTimeString(),'ip_address' => $userinfo->ip_address]);
        return $save;
    }
}
