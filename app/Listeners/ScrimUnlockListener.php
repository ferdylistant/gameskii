<?php

namespace App\Listeners;

use App\Events\ScrimUnlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrimUnlockListener
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
     * @param  ScrimUnlock  $event
     * @return void
     */
    public function handle(ScrimUnlock $event)
    {
        $scrimMaster = $event->scrimMaster;
        $save = DB::table('scrims')
            ->where('id', $scrimMaster->id)
            ->update(['result' => 'Prepare']);
        return $save;
    }
}
