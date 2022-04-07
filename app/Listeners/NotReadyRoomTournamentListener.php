<?php

namespace App\Listeners;

use App\Events\NotReadyRoomTournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotReadyRoomTournamentListener
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
     * @param  NotReadyRoomTournament  $event
     * @return void
     */
    public function handle(NotReadyRoomTournament $event)
    {
        $tournamentMatch = $event->tournamentMatch;
        $save = DB::table('tournament_matches')
            ->where('id', $tournamentMatch->id)
            ->update(['result' => 'Not yet']);
        return $save;
    }
}
