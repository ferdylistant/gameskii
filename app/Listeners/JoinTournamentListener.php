<?php

namespace App\Listeners;

use App\Events\JoinTournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JoinTournamentListener
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
     * @param  JoinTournament  $event
     * @return void
     */
    public function handle(JoinTournament $event)
    {
        $tournamentMatch = $event->tournamentMatch;
        $save = DB::table('tournament_matches')->insert($tournamentMatch);
        return $save;
    }
}
