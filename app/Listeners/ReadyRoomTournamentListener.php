<?php

namespace App\Listeners;

use App\Events\ReadyRoomTournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReadyRoomTournamentListener
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
     * @param  ReadyRoomTournament  $event
     * @return void
     */
    public function handle(ReadyRoomTournament $event)
    {
        $tournamentMatch = $event->tournamentMatch;
        $save = DB::table('tournament_matches')
            ->where('id', $tournamentMatch->id)
            ->update(['result' => 'Ready']);
        return $save;
    }
}
