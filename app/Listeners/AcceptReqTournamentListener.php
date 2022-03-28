<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use App\Events\AcceptReqTournament;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AcceptReqTournamentListener
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
     * @param  AcceptReqTournament  $event
     * @return void
     */
    public function handle(AcceptReqTournament $event)
    {
        $requestTeam = $event->requestTeam;
        $save = DB::table('tournament_matches')->where('id', $requestTeam->id)->update(['status_match' => '1']);
        return $save;
    }
}
