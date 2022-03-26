<?php

namespace App\Listeners;

use App\Events\LastLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $save = $userinfo->forceFill([
            'last_login' => $current->toDateTimeString(),
            'ip_address' => $request->getClientIp()])->save();
        return $save;
    }
}
