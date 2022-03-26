<?php

namespace App\Listeners;

use App\Events\JoinScrim;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JoinScrimListener
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
     * @param  JoinScrim  $event
     * @return void
     */
    public function handle(JoinScrim $event)
    {
        $scrimMatch = $event->scrimMatch;
        $insert = DB::table('scrim_matches')->insert($scrimMatch);
        return $insert;
    }
}
