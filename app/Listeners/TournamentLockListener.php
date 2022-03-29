<?php

namespace App\Listeners;

use App\Events\TournamentLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TournamentLockListener
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
     * @param  TournamentLock  $event
     * @return void
     */
    public function handle(TournamentLock $event)
    {
        $tournament = $event->tournament;
        $save = DB::table('tournaments')
            ->where('id', $tournament->id)
            ->update(['result' => 'Lock']);
        return $save;
    }
}
