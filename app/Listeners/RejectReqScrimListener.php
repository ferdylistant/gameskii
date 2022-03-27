<?php

namespace App\Listeners;

use App\Events\RejectReqScrim;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RejectReqScrimListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RejectReqScrim  $event
     * @return void
     */
    public function handle(RejectReqScrim $event)
    {
        $scrimMatch = $event->scrimMatch;
        $delete = DB::table('scrim_matches')->where('id', $scrimMatch->id)->delete();
        return $delete;
    }
}
