<?php

namespace App\Listeners;

use App\Events\ScrimStart;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ScrimStartListener
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
     * @param  ScrimStart  $event
     * @return void
     */
    public function handle(ScrimStart $event)
    {
        $scrimLock = $event->scrimLock;
        $save = DB::table('scrims')->where('id', $scrimLock->id)->update(['result' => 'Battle']);
        return $save;
    }
}
