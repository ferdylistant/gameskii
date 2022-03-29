<?php

namespace App\Listeners;

use App\Events\TournamentStart;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TournamentStartListener
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
     * @param  TournamentStart  $event
     * @return void
     */
    public function handle(TournamentStart $event)
    {
        $tournamentLock = $event->tournamentLock;
        $save = DB::table('tournaments')
            ->where('id', $tournamentLock->id)
            ->update(['result' => 'Battle']);
        return $save;
    }
}
