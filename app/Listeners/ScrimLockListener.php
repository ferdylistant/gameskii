<?php

namespace App\Listeners;

use App\Events\ScrimLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrimLockListener
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
     * @param  ScrimLock  $event
     * @return void
     */
    public function handle(ScrimLock $event)
    {
        $scrimMaster = $event->scrimMaster;
        $save = DB::table('scrims')
            ->where('id', $scrimMaster->id)
            ->update(['result' => 'Lock']);
        return $save;
    }
}
