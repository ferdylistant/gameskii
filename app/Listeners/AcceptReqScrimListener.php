<?php

namespace App\Listeners;

use App\Events\AcceptReqScrim;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AcceptReqScrimListener
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
     * @param  AcceptReqScrim  $event
     * @return void
     */
    public function handle(AcceptReqScrim $event)
    {
        $scrimMatch = $event->scrimMatch;
        $save = DB::table('scrim_matches')
            ->where('id', $scrimMatch->id)
            ->update(['status_match' => '1']);
        return $save;
    }
}
