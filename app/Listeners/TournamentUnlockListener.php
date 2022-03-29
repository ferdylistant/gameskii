<?php

namespace App\Listeners;

use App\Events\TournamentUnlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TournamentUnlockListener
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
     * @param  TournamentUnlock  $event
     * @return void
     */
    public function handle(TournamentUnlock $event)
    {
        $tournament = $event->tournament;
        $save = DB::table('tournaments')
            ->where('id', $tournament->id)
            ->update(['result' => 'Prepare']);
        return $save;
    }
}
