<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use App\Events\RejectReqTournament;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RejectReqTournamentListener
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
     * @param  RejectReqTournament  $event
     * @return void
     */
    public function handle(RejectReqTournament $event)
    {
        $tournamentMatch = $event->tournamentMatch;
        $delete = DB::table('tournament_matches')->where('id', $tournamentMatch->id)->delete();
        return $delete;
    }
}
