<?php

namespace App\Listeners;

use App\Events\ReadyRoomScrim;
use Illuminate\Support\Facades\DB;

class ReadyRoomScrimListener
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
     * @param  ReadyRoomScrim  $event
     * @return void
     */
    public function handle(ReadyRoomScrim $event)
    {
        $scrimMatch = $event->scrimMatch;
        $save = DB::table('scrim_matches')
            ->where('id', $scrimMatch->id)
            ->update(['result' => 'Ready']);
        return $save;
    }
}
