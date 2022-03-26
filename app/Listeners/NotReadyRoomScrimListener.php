<?php

namespace App\Listeners;

use App\Events\NotReadyRoomScrim;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotReadyRoomScrimListener
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
     * @param  NotReadyRoomScrim  $event
     * @return void
     */
    public function handle(NotReadyRoomScrim $event)
    {
        $scrimMatch = $event->scrimMatch;
        $save = DB::table('scrim_matches')
            ->where('id', $scrimMatch->id)
            ->update(['result' => 'Not yet']);
        return $save;
    }
}
